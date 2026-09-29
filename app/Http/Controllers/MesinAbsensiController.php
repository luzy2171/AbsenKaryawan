<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SolutionX100CService;
use App\Models\Karyawan;
use App\Models\MachineStatus;
use App\Services\SolutionSoapService;

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
            $usersByMachine[$machine->id] = array_column($machineUsers, 'pin');

            foreach ($machineUsers as $user) {
                $user['machine_id'] = $machine->id;
                $user['machine_name'] = $machine->machine_name;
                $usersSol[] = $user;
            }
        }

        $karyawans = Karyawan::orderBy('id_karyawan')->get();

        return view('admin.mesin.index', compact('machines', 'usersSol', 'usersByMachine', 'karyawans'));
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
            if (empty($user['pin'])) {
                continue;
            }

            if (!Karyawan::where('id_karyawan', $user['pin'])->exists()) {
                Karyawan::create([
                    'id_karyawan' => $user['pin'],
                    'nama' => $user['name'] ?: 'User ' . $user['pin'],
                    'status' => 'Aktif',
                ]);
                $berhasil++;
            }
        }

        return back()->with('status', "Berhasil menarik {$berhasil} data pengguna baru dari {$machine->machine_name} ke database lokal.");
    }

    public function kirimData(Request $request, SolutionX100CService $zkService, SolutionSoapService $soapService)
    {
        $request->validate([
            'karyawan_id' => 'required|integer|exists:karyawans,id',
            'machine_id' => 'required|integer|exists:machine_status,id',
        ]);

        $karyawan = Karyawan::findOrFail($request->integer('karyawan_id'));
        $machine = $this->solutionMachine($request->integer('machine_id'));
        $service = $this->serviceForMachine($machine, $zkService, $soapService);
        $result = $service->uploadNama($karyawan->id_karyawan, $karyawan->nama);
        $machine->updateStatus($service->wasLastConnectionSuccessful());

        if ($result !== 'Sukses') {
            return back()->with('error', "Gagal mengirim {$karyawan->nama} ke {$machine->machine_name}.");
        }

        return back()->with('status', "{$karyawan->nama} (PIN: {$karyawan->id_karyawan}) berhasil dikirim ke {$machine->machine_name}.");
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
            if (!in_array((string) $user['pin'], $localKaryawans, true)) {
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
            'machine_ip' => 'required|ip|unique:machine_status,machine_ip,' . $machine->id,
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
