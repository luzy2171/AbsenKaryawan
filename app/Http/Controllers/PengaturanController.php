<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SolutionX100CService;
use App\Services\SolutionSoapService;
use App\Helpers\AuditLogger;
use App\Models\MachineStatus;
use App\Models\Karyawan;

class PengaturanController extends Controller
{
    /**
     * Mengakses Halaman Dashboard Menu Pengaturan Alat
     */
    public function index(SolutionX100CService $zkService, SolutionSoapService $soapService, Request $request)
    {
        $machineStatuses = MachineStatus::orderByDesc('is_default')->orderBy('id')->get();
        $primaryMachine = $this->getPrimaryMachine();

        $users = [];
        $logs = [];
        $templates = [];
        $currentMachine = null;

        if ($primaryMachine) {
            $service = $this->serviceForMachine($primaryMachine, $zkService, $soapService);
            $pingTime = microtime(true);
            $isOnline = $this->pingService($service);
            $primaryMachine->updateStatus($isOnline, $isOnline ? round((microtime(true) - $pingTime) * 1000) : null);
            $currentMachine = MachineStatus::find($primaryMachine->id);
        }

        $shouldViewUsers = $request->has('view_users') || (!$request->has('view_logs') && !$request->has('download_fp'));

        if ($shouldViewUsers && $primaryMachine) {
            list($users, $connected) = $this->getUsersFromMachineWithStatus($service, $primaryMachine);
            if (!$connected) {
                $primaryMachine->updateStatus(false);
                $currentMachine = MachineStatus::find($primaryMachine->id);
            }
        }

        if ($request->has('view_logs') && $primaryMachine) {
            list($logs, $connected) = $this->getLogsFromMachineWithStatus($service, $primaryMachine);
            if (!$connected) {
                $primaryMachine->updateStatus(false);
                $currentMachine = MachineStatus::find($primaryMachine->id);
            }
        }

        $solutionTransport = strtolower((string) env('SOLUTION_TRANSPORT', 'soap'));
        $solutionSoapPort = (int) env('SOLUTION_SOAP_PORT', 80);

        return view('pengaturan.index', compact('users', 'logs', 'templates', 'machineStatuses', 'currentMachine', 'solutionTransport', 'solutionSoapPort'));
    }

    /**
     * Tambah Device/Mesin Baru
     */
    public function storeMachine(Request $request)
    {
        $request->validate([
            'machine_ip' => 'required|ip|unique:machine_status,machine_ip',
            'machine_name' => 'required|string|max:255',
            'machine_type' => 'nullable|in:solution,x100c',
            'port' => 'nullable|integer|min:1|max:65535',
        ]);

        $machine = MachineStatus::create([
            'machine_ip' => $request->machine_ip,
            'machine_name' => $request->machine_name,
            'machine_type' => $request->machine_type ?? 'solution',
            'port' => $request->port ?? 4370,
            'status' => 'offline',
            'is_default' => MachineStatus::count() === 0,
        ]);

        AuditLogger::machineAdded($request->machine_ip, $request->machine_name);

        return redirect()->back()->with('status', "Device {$request->machine_name} ({$request->machine_ip}) berhasil ditambahkan!");
    }

    /**
     * Update status Device/Mesin
     */
    public function updateMachineStatus(Request $request, $id)
    {
        $machine = MachineStatus::findOrFail($id);

        if ($request->has('status')) {
            $isOnline = $request->status === 'online';
            $machine->updateStatus($isOnline);
        }

        return response()->json([
            'success' => true,
            'status' => $machine->status,
            'message' => "Status {$machine->machine_name} diperbarui menjadi {$machine->status}"
        ]);
    }

    /**
     * Delete Device/Mesin
     */
    public function destroyMachine($id)
    {
        $machine = MachineStatus::findOrFail($id);
        $ip = $machine->machine_ip;
        $name = $machine->machine_name;

        $wasDefault = $machine->isDefault();
        $machine->delete();

        if ($wasDefault) {
            $replacement = MachineStatus::whereIn('machine_type', ['solution', 'x100c'])->orderBy('id')->first();
            if ($replacement) {
                $replacement->update(['is_default' => true]);
            }
        }

        AuditLogger::machineDeleted($ip, $name);

        return redirect()->back()->with('status', "Device {$name} ({$ip}) berhasil dihapus!");
    }

    /**
     * Ping mesin untuk cek status
     */
    public function pingMachine(SolutionX100CService $zkService, SolutionSoapService $soapService, $id)
    {
        $machine = MachineStatus::whereIn('machine_type', ['solution', 'x100c'])->findOrFail($id);
        $startTime = microtime(true);
        $service = $this->serviceForMachine($machine, $zkService, $soapService);
        $isOnline = $this->pingService($service);

        $responseTime = $isOnline ? round((microtime(true) - $startTime) * 1000) : null;
        $machine->updateStatus($isOnline, $responseTime);

        $message = $isOnline
            ? "Mesin {$machine->machine_name} berhasil di-ping! Response: {$responseTime}ms"
            : "Mesin {$machine->machine_name} tidak dapat dihubungi!";

        return back()->with('status', $message);
    }

    /**
     * Set default machine
     */
    public function setDefaultMachine($id)
    {
        $machine = MachineStatus::whereIn('machine_type', ['solution', 'x100c'])->findOrFail($id);

        MachineStatus::transaction(function () use ($machine) {
            MachineStatus::query()->update(['is_default' => false]);
            $machine->update(['is_default' => true]);
        });

        return redirect()->back()->with('status', "Mesin {$machine->machine_name} berhasil dijadikan default!");
    }

    /**
     * Proses Kosongkan Log Transaksi Mesin Absensi
     */
    public function clearMachineLogs(SolutionX100CService $zkService, SolutionSoapService $soapService)
    {
        $machine = $this->getPrimaryMachine();
        if (!$machine) {
            return back()->with('error', 'Tidak ada mesin yang dipilih. Tentukan mesin terlebih dahulu.');
        }

        $result = $this->executeOnMachine($zkService, $soapService, $machine, function ($s, $m) {
            return $s->clearLogData();
        });

        if ($result !== 'Sukses') {
            return back()->with('error', 'Gagal membersihkan log mesin absensi.');
        }

        $machine->updateStatus(true);
        AuditLogger::machineClearLog();

        return back()->with('status', 'Log transaksi mesin berhasil dibersihkan! Respon Alat: ' . $result);
    }

    /**
     * Proses Hapus User Langsung dari Menu Pengaturan
     */
    public function hapusUserDariMesin(Request $request, SolutionX100CService $zkService, SolutionSoapService $soapService)
    {
        $request->validate([
            'machine_id' => 'nullable|integer|exists:machine_status,id',
            'user_id' => ['required', 'regex:/^\d{1,9}$/'],
        ]);

        $machine = $request->filled('machine_id')
            ? MachineStatus::whereIn('machine_type', ['solution', 'x100c'])->findOrFail($request->integer('machine_id'))
            : $this->getPrimaryMachine();
        if (!$machine) {
            return back()->with('error', 'Tidak ada mesin yang dipilih.');
        }

        $result = $this->executeOnMachine($zkService, $soapService, $machine, function ($s, $m) use ($request) {
            return $s->hapusUser($request->input('user_id'));
        });

        if ($result !== 'Sukses') {
            return back()->with('error', 'Gagal menghapus user dari mesin absensi.');
        }

        $machine->updateStatus(true);
        AuditLogger::machineUserDeleted($request->input('user_id'));

        return back()->with('status', 'Proses Hapus User Berhasil! Respon Alat: ' . $result);
    }

    /**
     * Memproses Sinkronisasi Waktu Server ke Perangkat Absensi Fisik
     */
    public function synchronizeDeviceTime(SolutionX100CService $zkService, SolutionSoapService $soapService)
    {
        $machine = $this->getPrimaryMachine();
        if (!$machine) {
            return back()->with('error', 'Tidak ada mesin yang dipilih.');
        }

        $result = $this->executeOnMachine($zkService, $soapService, $machine, function ($s, $m) {
            return $s->syncTime();
        });

        if (!is_string($result) || !str_starts_with($result, 'Waktu berhasil')) {
            return back()->with('error', 'Gagal menyamakan waktu. Koneksi ke mesin terputus.');
        }

        $machine->updateStatus(true);
        AuditLogger::machineSync();

        return back()->with('status', 'Waktu mesin berhasil disinkronkan dengan server web! Respon: ' . $result);
    }

    /**
     * Memproses Perintah Restart Mesin Absensi Fisik
     */
    public function restartMachine(SolutionX100CService $zkService, SolutionSoapService $soapService)
    {
        $machine = $this->getPrimaryMachine();
        if (!$machine) {
            return back()->with('error', 'Tidak ada mesin yang dipilih.');
        }

        $result = $this->executeOnMachine($zkService, $soapService, $machine, function ($s, $m) {
            return $s->restartDevice();
        });

        if ($result !== 'Sukses') {
            return back()->with('error', 'Gagal merestart perangkat. Koneksi ke mesin terputus.');
        }

        AuditLogger::machineRestart();

        return back()->with('status', 'Perintah restart berhasil dikirim! Mesin absensi sedang memuat ulang. Respon: ' . $result);
    }

    // ===================== HELPERS =====================

    protected function getPrimaryMachine()
    {
        return MachineStatus::defaultForType('solution');
    }

    protected function executeOnMachine(SolutionX100CService $zkService, SolutionSoapService $soapService, $machine, callable $callback)
    {
        $service = $this->serviceForMachine($machine, $zkService, $soapService);
        return $callback($service, $machine);
    }

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

    protected function pingService($service): bool
    {
        if ($service instanceof SolutionSoapService) {
            return $service->ping();
        }

        if ($service instanceof SolutionX100CService) {
            $online = $service->connect();
            if ($online) {
                $service->disconnect();
            }
            return $online;
        }

        return false;
    }

    protected function getUsersFromMachineWithStatus($service, $machine)
    {
        if (!$machine) return [[], false];
        $users = $service->getAllUsers();
        return [$users, $service->wasLastConnectionSuccessful()];
    }

    protected function getLogsFromMachineWithStatus($service, $machine)
    {
        if (!$machine) return [[], false];
        $logs = $service->downloadLogTigaBulan();

        foreach ($logs as &$log) {
            $karyawan = Karyawan::where('id_karyawan', $log['pin'])->first();
            $log['name'] = $karyawan ? $karyawan->nama : '-';
        }

        return [$logs, $service->wasLastConnectionSuccessful()];
    }
}
