<?php

namespace App\Http\Controllers;

use App\Models\Fingerprint;
use App\Models\Karyawan;
use App\Models\MachineStatus;
use App\Services\SolutionSoapService;
use App\Services\SolutionX100CService;
use Illuminate\Http\Request;

class MesinAbsensiController extends Controller
{
    public function index(SolutionX100CService $zkService, SolutionSoapService $soapService)
    {
        $machines = MachineStatus::whereIn('machine_type', ['solution', 'x100c'])->orderByDesc('is_default')->orderBy('id')->get();
        $usersSol = [];
        $usersByMachine = [];

        foreach ($machines as $machine) {
            $service = $this->serviceForMachine($machine, $zkService, $soapService);
            $machineUsers = $service->getAllUsers();
            $machine->updateStatus($service->wasLastConnectionSuccessful());
            // Sinkronisasi dicocokkan ke id_karyawan, yang di mesin muncul sebagai
            // <PIN2> pada log absensi. <PIN> dipakai juga karena sebagian mesin
            // memakai PIN == PIN2.
            $usersByMachine[$machine->id] = array_values(array_unique(array_filter(
                array_merge(
                    array_column($machineUsers, 'pin'),
                    array_column($machineUsers, 'pin2')
                ),
                static fn ($v) => $v !== '' && $v !== null
            )));

            foreach ($machineUsers as $user) {
                $user['machine_id'] = $machine->id;
                $user['machine_name'] = $machine->machine_name;
                $usersSol[] = $user;
            }
        }

        $karyawans = Karyawan::orderBy('id_karyawan')->get();
        $fingerprints = Fingerprint::with(['karyawan', 'machine'])
            ->orderByDesc('updated_at')
            ->get();

        return view('admin.mesin.index', compact('machines', 'usersSol', 'usersByMachine', 'karyawans', 'fingerprints'));
    }

    public function tarikDataAll(Request $request, SolutionX100CService $zkService, SolutionSoapService $soapService)
    {
        $request->validate([
            'machine_id' => 'required|integer|exists:machine_status,id',
        ]);

        $machine = $this->solutionMachine($request->integer('machine_id'));
        $service = $this->serviceForMachine($machine, $zkService, $soapService);
        $users = $service->getAllUsers();
        $machine->updateStatus($service->wasLastConnectionSuccessful());

        $berhasil = 0;
        foreach ($users as $user) {
            // Id user yang muncul di log absensi = <PIN2>. Fallback ke <PIN>
            // untuk mesin yang memakai PIN == PIN2.
            $idUser = trim((string) ($user['pin2'] ?? '')) ?: trim((string) ($user['pin'] ?? ''));

            if ($idUser === '') {
                continue;
            }

            if (! Karyawan::where('id_karyawan', $idUser)->exists()) {
                Karyawan::create([
                    'id_karyawan' => $idUser,
                    'nama' => $user['name'] ?: 'User '.$idUser,
                    'status' => 'Aktif',
                ]);
                $berhasil++;
            }
        }

        return back()->with('status', "Berhasil menarik {$berhasil} data pengguna baru dari {$machine->machine_name} ke database lokal.");
    }

    /**
     * Kirim satu atau banyak karyawan ke mesin sekaligus.
     */
    public function kirimData(Request $request, SolutionX100CService $zkService, SolutionSoapService $soapService)
    {
        $request->validate([
            'karyawan_id' => ['required', 'array', 'min:1'],
            'karyawan_id.*' => ['integer', 'exists:karyawans,id'],
            'machine_id' => 'required|integer|exists:machine_status,id',
        ]);

        $ids = array_values(array_unique(array_map('intval', $request->input('karyawan_id'))));
        $karyawans = Karyawan::whereIn('id', $ids)->orderBy('nama')->get();

        $machine = $this->solutionMachine($request->integer('machine_id'));
        $service = $this->serviceForMachine($machine, $zkService, $soapService);

        $berhasil = [];
        $gagal = [];

        foreach ($karyawans as $karyawan) {
            $result = $service->uploadNama($karyawan->id_karyawan, $karyawan->nama);

            if ($result === 'Sukses') {
                $berhasil[] = $karyawan->nama;
            } else {
                $gagal[] = $karyawan->nama.' ('.$result.')';
            }
        }

        $machine->updateStatus($service->wasLastConnectionSuccessful());

        $pesan = [];
        if ($berhasil !== []) {
            $pesan[] = count($berhasil).' karyawan berhasil dikirim ke '.$machine->machine_name.': '
                .implode(', ', $berhasil);
        }
        if ($gagal !== []) {
            $pesan[] = count($gagal).' gagal: '.implode(', ', $gagal);
        }

        return back()->with($gagal === [] ? 'status' : 'error', implode(' | ', $pesan));
    }

    /**
     * Tarik template sidik jari dari mesin ke database lokal.
     *
     * Pengguna cukup mendaftarkan sidik jarinya langsung di mesin; aplikasi ini
     * menarik template-nya lewat SOAP GetUserTemplate.
     */
    public function tarikSidikJari(Request $request, SolutionX100CService $zkService, SolutionSoapService $soapService)
    {
        $request->validate([
            'machine_id' => 'required|integer|exists:machine_status,id',
            'karyawan_id' => 'required|integer|exists:karyawans,id',
        ]);

        $machine = $this->solutionMachine($request->integer('machine_id'));
        $karyawan = Karyawan::findOrFail($request->integer('karyawan_id'));

        $service = $this->serviceForMachine($machine, $zkService, $soapService);

        if (! $service instanceof SolutionSoapService) {
            return back()->with('error', 'Tarik sidik jari hanya bisa untuk mesin bertipe Solution (SOAP).');
        }

        $templates = $service->getUserTemplates($karyawan->id_karyawan);
        $machine->updateStatus($service->wasLastConnectionSuccessful());

        if ($templates === []) {
            Fingerprint::where('karyawan_id', $karyawan->id)
                ->where('machine_id', $machine->id)
                ->delete();

            return back()->with('error', "Tidak ada sidik jari terdaftar di mesin untuk {$karyawan->nama} (PIN: {$karyawan->id_karyawan}).");
        }

        $tersimpan = [];
        foreach ($templates as $t) {
            $templateAsli = base64_decode($t['template'], true);
            if ($templateAsli === false) {
                continue;
            }

            Fingerprint::updateOrCreate(
                [
                    'machine_id' => $machine->id,
                    'karyawan_id' => $karyawan->id,
                    'finger_id' => $t['finger_id'],
                ],
                [
                    'template' => $t['template'],
                    'size' => strlen($templateAsli),
                ]
            );

            $tersimpan[] = $t['finger_id'];
        }

        return back()->with('status', count($tersimpan).' sidik jari ditarik dari mesin untuk '
            ."{$karyawan->nama} (jari slot: ".implode(', ', $tersimpan).').');
    }

    /**
     * Kirim template sidik jari ke mesin (SOAP SetUserTemplate + RefreshDB).
     *
     * Template dibaca dari file hasil export software PC mesin. File boleh berupa
     * base64 (teks) atau biner mentah - keduanya dinormalkan ke base64 karena
     * itulah format yang dipakai mesin di dalam XML.
     */
    public function kirimSidikJari(Request $request, SolutionX100CService $zkService, SolutionSoapService $soapService)
    {
        $data = $request->validate([
            'machine_id' => 'required|integer|exists:machine_status,id',
            'karyawan_id' => 'required|integer|exists:karyawans,id',
            'finger_id' => 'required|integer|min:0|max:9',
            'template' => 'required|file|max:8',
        ]);

        $machine = $this->solutionMachine((int) $data['machine_id']);
        $karyawan = Karyawan::findOrFail((int) $data['karyawan_id']);

        $service = $this->serviceForMachine($machine, $zkService, $soapService);
        if (! $service instanceof SolutionSoapService) {
            return back()->with('error', 'Kirim sidik jari hanya bisa untuk mesin bertipe Solution (SOAP).');
        }

        $template = $this->normalisasiTemplateBase64($request->file('template')->get());

        if ($template === null) {
            return back()->with('error', 'File template tidak bisa dibaca sebagai data sidik jari (harus base64 atau biner).');
        }

        if (strlen($template) > 8192) {
            return back()->with('error', 'Template terlalu besar ('.number_format(strlen($template))
                .' karakter). Maksimal 8192 karakter base64.');
        }

        $result = $service->setUserTemplate($karyawan->id_karyawan, (int) $data['finger_id'], $template);
        $machine->updateStatus($service->wasLastConnectionSuccessful());

        if ($result !== 'Sukses') {
            return back()->with('error', "Gagal mengirim sidik jari ke {$machine->machine_name}: {$result}");
        }

        Fingerprint::updateOrCreate(
            [
                'machine_id' => $machine->id,
                'karyawan_id' => $karyawan->id,
                'finger_id' => (int) $data['finger_id'],
            ],
            [
                'template' => $template,
                'size' => strlen((string) base64_decode($template, true)),
            ]
        );

        $jari = (new Fingerprint(['finger_id' => (int) $data['finger_id']]))->namaJari();

        return back()->with('status', "Sidik jari ({$jari}) untuk {$karyawan->nama} berhasil dikirim ke {$machine->machine_name}.");
    }

    /**
     * Ubah isi file menjadi base64 yang valid untuk dikirim ke mesin.
     *
     * File hasil export bisa berupa teks base64 atau biner mentah. Biner mentah
     * dibungkus dengan base64_encode; kalau teks sudah base64, dipakai apa adanya.
     *
     * Penting: file biner sama sekali TIDAK boleh di-trim, karena byte spasi /
     * newline di ujung adalah bagian dari template dan memotongnya membuat
     * sidik jari tidak terbaca mesin.
     */
    private function normalisasiTemplateBase64(string $isi): ?string
    {
        if ($isi === '') {
            return null;
        }

        // Cek apakah isinya teks base64: coerce dulu ke string aman.
        $teks = trim($isi);
        $rapikan = $teks === '' ? '' : (preg_replace('/\s+/', '', $teks) ?? '');

        if ($rapikan !== ''
            && preg_match('#^[A-Za-z0-9+/]+={0,2}$#', $rapikan) === 1
            && strlen($rapikan) % 4 === 0) {
            $decode = base64_decode($rapikan, true);
            if ($decode !== false && $decode !== '') {
                return $rapikan;
            }
        }

        // Biner mentah -> base64. Isi file dipakai apa adanya, tanpa trim.
        return base64_encode($isi);
    }

    /**
     * Hapus template sidik jari dari database lokal.
     */
    public function hapusSidikJari(Request $request, $id)
    {
        $fingerprint = Fingerprint::findOrFail($id);
        $nama = optional($fingerprint->karyawan)->nama ?? 'Karyawan';
        $jari = $fingerprint->namaJari();

        $fingerprint->delete();

        return back()->with('status', "Sidik jari {$jari} untuk {$nama} dihapus dari database lokal (di mesin tidak berubah).");
    }

    public function hapusData($mesin, $pin, SolutionX100CService $zkService, SolutionSoapService $soapService)
    {
        $machine = $this->routeMachine($mesin);
        $service = $this->serviceForMachine($machine, $zkService, $soapService);
        $result = $service->hapusUser($pin);
        $machine->updateStatus($service->wasLastConnectionSuccessful());

        if ($result !== 'Sukses') {
            return back()->with('error', "Gagal menghapus PIN {$pin} dari {$machine->machine_name}.");
        }

        return back()->with('status', "PIN {$pin} berhasil dihapus dari {$machine->machine_name}.");
    }

    public function updateKaryawan(Request $request, $id)
    {
        $karyawan = Karyawan::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:255',
            'departemen' => 'nullable|string|max:255',
            'jabatan' => 'nullable|string|max:255',
        ]);

        $karyawan->update([
            'nama' => $request->nama,
            'departemen' => $request->departemen,
            'jabatan' => $request->jabatan,
        ]);

        return back()->with('status', "Data karyawan {$karyawan->nama} berhasil diubah di database lokal.");
    }

    public function hapusKaryawanDB($id)
    {
        $karyawan = Karyawan::findOrFail($id);
        $nama = $karyawan->nama;
        $karyawan->delete();

        return back()->with('status', "Data karyawan {$nama} berhasil dihapus DARI DATABASE LOKAL SAJA (Tetap ada di memori mesin).");
    }

    public function cleanUnsynced($mesin, SolutionX100CService $zkService, SolutionSoapService $soapService)
    {
        $machine = $this->routeMachine($mesin);
        $localKaryawans = array_map('strval', Karyawan::pluck('id_karyawan')->toArray());
        $service = $this->serviceForMachine($machine, $zkService, $soapService);
        $users = $service->getAllUsers();
        $deletedCount = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            if (! in_array((string) $user['pin'], $localKaryawans, true)) {
                $result = $service->hapusUser($user['pin']);
                if ($result === 'Sukses') {
                    $deletedCount++;
                } else {
                    $failedCount++;
                }
            }
        }

        $message = "Pembersihan selesai! {$deletedCount} data asing berhasil dihapus dari {$machine->machine_name}.";
        if ($failedCount > 0) {
            $message .= " ({$failedCount} gagal)";
        }

        return back()->with('status', $message);
    }

    public function storeDevice(Request $request)
    {
        $validated = $request->validate([
            'machine_ip' => 'required|ip|unique:machine_status,machine_ip',
            'machine_name' => 'required|string|max:255',
            'machine_type' => 'required|in:solution,x100c',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
        ]);

        MachineStatus::create([
            'machine_ip' => $validated['machine_ip'],
            'machine_name' => $validated['machine_name'],
            'machine_type' => $validated['machine_type'],
            'port' => $validated['port'],
            'username' => $validated['username'] ?? null,
            'password' => $validated['password'] ?? null,
            'status' => 'offline',
            'is_default' => MachineStatus::count() === 0,
        ]);

        return redirect()->back()->with('status', "Device {$validated['machine_name']} berhasil ditambahkan!");
    }

    public function updateDevice(Request $request, $id)
    {
        $machine = MachineStatus::findOrFail($id);

        $validated = $request->validate([
            'machine_ip' => 'required|ip|unique:machine_status,machine_ip,'.$machine->id,
            'machine_name' => 'required|string|max:255',
            'machine_type' => 'required|in:solution,x100c',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
        ]);

        $machine->update([
            'machine_ip' => $validated['machine_ip'],
            'machine_name' => $validated['machine_name'],
            'machine_type' => $validated['machine_type'],
            'port' => $validated['port'],
        ]);

        if ($request->filled('username')) {
            $machine->update(['username' => $validated['username']]);
        }

        if ($request->filled('password')) {
            $machine->update(['password' => $validated['password']]);
        }

        return redirect()->back()->with('status', "Device {$validated['machine_name']} berhasil diperbarui!");
    }

    public function destroyDevice($id)
    {
        $machine = MachineStatus::findOrFail($id);
        $wasDefault = $machine->isDefault();
        $machine->delete();

        if ($wasDefault) {
            $replacement = MachineStatus::whereIn('machine_type', ['solution', 'x100c'])->orderBy('id')->first();
            if ($replacement) {
                $replacement->update(['is_default' => true]);
            }
        }

        return redirect()->back()->with('status', 'Device berhasil dihapus.');
    }

    public function pingDevice($id, SolutionX100CService $zkService, SolutionSoapService $soapService)
    {
        $machine = $this->solutionMachine($id);
        $startTime = microtime(true);
        $service = $this->serviceForMachine($machine, $zkService, $soapService);
        $isOnline = $service instanceof SolutionSoapService ? $service->ping() : $service->connect();
        if ($isOnline && $service instanceof SolutionX100CService) {
            $service->disconnect();
        }
        $responseTime = round((microtime(true) - $startTime) * 1000);
        $machine->updateStatus($isOnline, $isOnline ? $responseTime : null);

        if ($isOnline) {
            return redirect()->back()->with('status', "Ping berhasil! {$machine->machine_name} Online (Response: {$responseTime}ms).");
        }

        return redirect()->back()->with('error', "Ping gagal! {$machine->machine_name} Offline.");
    }

    protected function solutionMachine($id)
    {
        return MachineStatus::whereIn('machine_type', ['solution', 'x100c'])->findOrFail($id);
    }

    protected function routeMachine($identifier)
    {
        if (is_numeric($identifier)) {
            return $this->solutionMachine((int) $identifier);
        }

        $machine = MachineStatus::defaultForType('solution');
        abort_unless($machine, 404, 'Mesin Solution tidak ditemukan.');

        return $machine;
    }

    /**
     * Pilih service yang sesuai dengan transport mesin.
     * Solution memakai SOAP, sedangkan tipe lain memakai protokol biner.
     */
    protected function serviceForMachine($machine, SolutionX100CService $zkService, SolutionSoapService $soapService)
    {
        if ($this->usesSoapTransport($machine)) {
            $soapService->setConnection(
                $machine->machine_ip,
                (int) env('SOLUTION_SOAP_PORT', 80),
                $machine->username
            );

            return $soapService;
        }

        $zkService->setConnection($machine->machine_ip, $machine->port ?? 4370);

        return $zkService;
    }

    protected function usesSoapTransport($machine): bool
    {
        $transport = strtolower((string) env('SOLUTION_TRANSPORT', 'soap'));
        if ($transport === 'soap') {
            return true;
        }

        if ($transport === 'binary') {
            return false;
        }

        return (int) ($machine->port ?? 0) === 80;
    }
}
