<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Karyawan;
use App\Models\MachineStatus;
use App\Services\SolutionSoapService;
use App\Services\SolutionX100CService;
use Illuminate\Http\Request;

class KaryawanController extends Controller
{
    /**
     * Menampilkan daftar karyawan di tabel
     */
    public function index()
    {
        $karyawans = Karyawan::orderBy('id_karyawan', 'asc')->get();

        return view('karyawan.index', compact('karyawans'));
    }

    /**
     * Menyimpan karyawan baru ke Database Web dan Mesin Absensi Fisik
     */
    public function store(Request $request)
    {
        if (! auth()->user()->canEdit()) {
            abort(403, 'Akses ditolak. Role Anda tidak dapat menambah karyawan.');
        }
        $request->validate([
            'id_karyawan' => ['required', 'regex:/^\d{1,5}$/', 'unique:karyawans,id_karyawan'],
            'nama' => 'required|string|max:255',
            'departemen' => 'nullable|string',
            'jabatan' => 'nullable|string',
        ]);

        // 2. Simpan ke database internal website
        $karyawan = Karyawan::create([
            'id_karyawan' => $request->id_karyawan,
            'nama' => $request->nama,
            'departemen' => $request->departemen,
            'jabatan' => $request->jabatan,
            'status' => 'Aktif',
        ]);

        // Log audit
        AuditLogger::karyawanCreated($karyawan);

        return redirect()->route('karyawan.index')->with('status', 'Karyawan berhasil ditambahkan ke Web.');
    }

    /**
     * FITUR BARU: Sinkronisasi Otomatis Semua User dari Perangkat ke Database Web
     */
    public function syncDariMesin(Request $request, SolutionX100CService $zktecoService, SolutionSoapService $soapService)
    {
        $request->validate([
            'mesin_tujuan' => 'nullable|in:solution,x100c',
        ]);

        $startTime = microtime(true);
        $machine = MachineStatus::defaultForType('solution');

        if (! $machine) {
            return back()->with('error', 'Tidak ada mesin Solution yang dikonfigurasi.');
        }

        $service = $this->serviceForMachine($machine, $zktecoService, $soapService);
        $zkUsers = $service->getAllUsers();
        $connected = $service->wasLastConnectionSuccessful();

        $usersDariMesin = [];
        $uniquePins = [];

        foreach ((array) $zkUsers as $user) {
            if (! isset($user['pin']) || $user['pin'] === '' || in_array($user['pin'], $uniquePins, true)) {
                continue;
            }

            $uniquePins[] = $user['pin'];
            $usersDariMesin[] = $user;
        }

        if (empty($usersDariMesin)) {
            $machine->updateStatus($connected, $connected ? round((microtime(true) - $startTime) * 1000) : null);

            return back()->with('error', 'Gagal mengambil data dari mesin. Pastikan mesin dalam kondisi terhubung (Online).');
        }

        // Hitung response time dan update status mesin menjadi online
        $responseTime = round((microtime(true) - $startTime) * 1000);
        $machine->updateStatus(true, $responseTime);

        $karyawanBaru = 0;

        // 2. Lakukan pengecekan dan penyimpanan data ke DB Web secara massal
        foreach ($usersDariMesin as $user) {
            $exists = Karyawan::where('id_karyawan', $user['pin'])->exists();

            if (! $exists) {
                Karyawan::create([
                    'id_karyawan' => $user['pin'],
                    'nama' => $user['name'],
                    'departemen' => '-',
                    'jabatan' => 'Staf',
                    'status' => 'Aktif',
                ]);
                $karyawanBaru++;
            }
        }

        // Log audit
        AuditLogger::karyawanSynced($karyawanBaru);

        return redirect()->route('karyawan.index')->with('status', "Sinkronisasi berhasil! Memproses $karyawanBaru data karyawan baru dari mesin absensi.");
    }

    /**
     * Menghapus karyawan dari Web
     */
    protected function serviceForMachine($machine, SolutionX100CService $binaryService, SolutionSoapService $soapService)
    {
        $transport = strtolower((string) env('SOLUTION_TRANSPORT', 'soap'));
        if ($transport !== 'binary' && ($transport === 'soap' || (int) ($machine->port ?? 0) === 80)) {
            $soapService->setConnection(
                $machine->machine_ip,
                (int) env('SOLUTION_SOAP_PORT', 80),
                $machine->username
            );

            return $soapService;
        }

        $binaryService->setConnection($machine->machine_ip, $machine->port ?? 4370);

        return $binaryService;
    }

    public function destroy($id)
    {
        if (! auth()->user()->isTrueApprover()) {
            abort(403, 'Akses ditolak. Hanya Approver dan Superadmin yang dapat menghapus karyawan.');
        }

        $karyawan = Karyawan::findOrFail($id);

        // Log audit before delete
        AuditLogger::karyawanDeleted($karyawan);

        // 2. Hapus dari database website
        $karyawan->delete();

        return back()->with('status', 'Data karyawan di web berhasil dihapus.');
    }
}
