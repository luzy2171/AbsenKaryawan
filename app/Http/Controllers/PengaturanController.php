<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SolutionX100CService;
use App\Helpers\AuditLogger;
use App\Models\MachineStatus;
use App\Models\Karyawan;

class PengaturanController extends Controller
{
    /**
     * Mengakses Halaman Dashboard Menu Pengaturan Alat
     */
    public function index(SolutionX100CService $zkService, Request $request)
    {
        $machineStatuses = MachineStatus::all();
        $primaryMachine = $this->getPrimaryMachine();

        $users = [];
        $logs = [];
        $templates = [];
        $currentMachine = null;

        // Try device connection first via ping so we get correct online/offline state
        if ($primaryMachine) {
            $pingTime = microtime(true);
            $pingConn = @stream_socket_client(
                "tcp://{$primaryMachine->machine_ip}:{$primaryMachine->port}",
                $errno,
                $errstr,
                3
            );
            if ($pingConn) {
                fclose($pingConn);
                $primaryMachine->updateStatus(true, round((microtime(true) - $pingTime) * 1000));
            } else {
                $primaryMachine->updateStatus(false);
            }
            $currentMachine = MachineStatus::find($primaryMachine->id);
        }

        $shouldViewUsers = $request->has('view_users') || (!$request->has('view_logs') && !$request->has('download_fp'));

        if ($shouldViewUsers && $primaryMachine) {
            list($users, $connected) = $this->getUsersFromMachineWithStatus($zkService, $primaryMachine);
            if (!$connected) {
                $primaryMachine->updateStatus(false);
                $currentMachine = MachineStatus::find($primaryMachine->id);
            }
        }

        if ($request->has('view_logs') && $primaryMachine) {
            list($logs, $connected) = $this->getLogsFromMachineWithStatus($zkService, $primaryMachine);
            if (!$connected) {
                $primaryMachine->updateStatus(false);
                $currentMachine = MachineStatus::find($primaryMachine->id);
            }
        }

        return view('pengaturan.index', compact('users', 'logs', 'templates', 'machineStatuses', 'currentMachine'));
    }

    /**
     * Tambah Device/Mesin Baru
     */
    public function storeMachine(Request $request)
    {
        $request->validate([
            'machine_ip' => 'required|ip|unique:machine_status,machine_ip',
            'machine_name' => 'required|string|max:255',
            'port' => 'nullable|integer',
        ]);

        $machine = MachineStatus::create([
            'machine_ip' => $request->machine_ip,
            'machine_name' => $request->machine_name,
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

        $machine->delete();

        AuditLogger::machineDeleted($ip, $name);

        return redirect()->back()->with('status', "Device {$name} ({$ip}) berhasil dihapus!");
    }

    /**
     * Ping mesin untuk cek status
     */
    public function pingMachine($id)
    {
        $machine = MachineStatus::findOrFail($id);

        $startTime = microtime(true);
        $connection = @stream_socket_client("tcp://{$machine->machine_ip}:{$machine->port}", $errno, $errstr, 3);
        $responseTime = $connection ? round((microtime(true) - $startTime) * 1000) : null;

        if ($connection) {
            fclose($connection);
            $machine->updateStatus(true, $responseTime);
            $message = "Mesin {$machine->machine_name} berhasil di-ping! Response: {$responseTime}ms";
        } else {
            $machine->updateStatus(false);
            $message = "Mesin {$machine->machine_name} tidak dapat dihubungi!";
        }

        return back()->with('status', $message);
    }

    /**
     * Set default machine
     */
    public function setDefaultMachine($id)
    {
        MachineStatus::query()->update(['is_default' => false]);

        $machine = MachineStatus::findOrFail($id);
        $machine->update(['is_default' => true]);

        return redirect()->back()->with('status', "Mesin {$machine->machine_name} berhasil dijadikan default!");
    }

    /**
     * Proses Kosongkan Log Transaksi Mesin Absensi
     */
    public function clearMachineLogs(SolutionX100CService $zkService)
    {
        $machine = $this->getPrimaryMachine();
        if (!$machine) {
            return back()->with('error', 'Tidak ada mesin yang dipilih. Tentukan mesin terlebih dahulu.');
        }

        $result = $this->executeOnMachine($zkService, $machine, function ($s, $m) {
            return $s->clearLogData();
        });

        if ($result === "Koneksi Gagal") {
            return back()->with('error', 'Gagal terhubung dengan mesin absensi.');
        }

        $machine->updateStatus(true);
        AuditLogger::machineClearLog();

        return back()->with('status', 'Log transaksi mesin berhasil dibersihkan! Respon Alat: ' . $result);
    }

    /**
     * Proses Hapus User Langsung dari Menu Pengaturan
     */
    public function hapusUserDariMesin(Request $request, SolutionX100CService $zkService)
    {
        $request->validate([
            'user_id' => 'required',
        ]);

        $machine = $this->getPrimaryMachine();
        if (!$machine) {
            return back()->with('error', 'Tidak ada mesin yang dipilih.');
        }

        $result = $this->executeOnMachine($zkService, $machine, function ($s, $m) use ($request) {
            return $s->hapusUser($request->input('user_id'));
        });

        if ($result === "Koneksi Gagal") {
            return back()->with('error', 'Gagal terhubung dengan mesin absensi.');
        }

        $machine->updateStatus(true);
        AuditLogger::machineUserDeleted($request->input('user_id'));

        return back()->with('status', 'Proses Hapus User Berhasil! Respon Alat: ' . $result);
    }

    /**
     * Memproses Sinkronisasi Waktu Server ke Perangkat Absensi Fisik
     */
    public function synchronizeDeviceTime(SolutionX100CService $zkService)
    {
        $machine = $this->getPrimaryMachine();
        if (!$machine) {
            return back()->with('error', 'Tidak ada mesin yang dipilih.');
        }

        $result = $this->executeOnMachine($zkService, $machine, function ($s, $m) {
            return $s->syncTime();
        });

        if ($result === "Koneksi Gagal") {
            return back()->with('error', 'Gagal menyamakan waktu. Koneksi ke mesin terputus.');
        }

        $machine->updateStatus(true);
        AuditLogger::machineSync();

        return back()->with('status', 'Waktu mesin berhasil disinkronkan dengan server web! Respon: ' . $result);
    }

    /**
     * Memproses Perintah Restart Mesin Absensi Fisik
     */
    public function restartMachine(SolutionX100CService $zkService)
    {
        $machine = $this->getPrimaryMachine();
        if (!$machine) {
            return back()->with('error', 'Tidak ada mesin yang dipilih.');
        }

        $result = $this->executeOnMachine($zkService, $machine, function ($s, $m) {
            return $s->restartDevice();
        });

        if ($result === "Koneksi Gagal") {
            return back()->with('error', 'Gagal merestart perangkat. Koneksi ke mesin terputus.');
        }

        AuditLogger::machineRestart();

        return back()->with('status', 'Perintah restart berhasil dikirim! Mesin absensi sedang memuat ulang. Respon: ' . $result);
    }

    // ===================== HELPERS =====================

    protected function getPrimaryMachine()
    {
        return MachineStatus::where('is_default', true)->first() ?? MachineStatus::first();
    }

    protected function executeOnMachine(SolutionX100CService $zkService, $machine, callable $callback)
    {
        $zkService->setConnection($machine->machine_ip, $machine->port ?? 4370);
        return $callback($zkService, $machine);
    }

    protected function getUsersFromMachine(SolutionX100CService $zkService, $machine)
    {
        if (!$machine) return [];
        $zkService->setConnection($machine->machine_ip, $machine->port ?? 4370);
        return $zkService->getAllUsers();
    }

    /**
     * Get users from machine AND return whether connection was successful
     * Returns [array $users, bool $connected]
     */
    protected function getUsersFromMachineWithStatus(SolutionX100CService $zkService, $machine)
    {
        if (!$machine) return [[], false];
        $zkService->setConnection($machine->machine_ip, $machine->port ?? 4370);
        $users = $zkService->getAllUsers();
        // If connect() failed inside getAllUsers(), the service disconnects internally.
        // We detect by checking if stream is null (service closed it due to failure).
        // Since getAllUsers() returns [] on failure, we re-attempt connect to know the real state.
        $connected = true;
        if (empty($users)) {
            $testStream = @stream_socket_client(
                "tcp://{$machine->machine_ip}:{$machine->port}",
                $errno,
                $errstr,
                3
            );
            if (!$testStream) {
                $connected = false;
            } else {
                fclose($testStream);
            }
        }
        return [$users, $connected];
    }

    protected function getLogsFromMachine(SolutionX100CService $zkService, $machine)
    {
        if (!$machine) return [];
        $zkService->setConnection($machine->machine_ip, $machine->port ?? 4370);
        $logs = $zkService->downloadLogTigaBulan();

        foreach ($logs as &$log) {
            $karyawan = Karyawan::where('id_karyawan', $log['pin'])->first();
            $log['name'] = $karyawan ? $karyawan->nama : '-';
        }

        return $logs;
    }

    /**
     * Get logs from machine AND return whether connection was successful
     * Returns [array $logs, bool $connected]
     */
    protected function getLogsFromMachineWithStatus(SolutionX100CService $zkService, $machine)
    {
        if (!$machine) return [[], false];
        $zkService->setConnection($machine->machine_ip, $machine->port ?? 4370);
        $logs = $zkService->downloadLogTigaBulan();

        foreach ($logs as &$log) {
            $karyawan = Karyawan::where('id_karyawan', $log['pin'])->first();
            $log['name'] = $karyawan ? $karyawan->nama : '-';
        }

        $connected = true;
        if (empty($logs)) {
            $testStream = @stream_socket_client(
                "tcp://{$machine->machine_ip}:{$machine->port}",
                $errno,
                $errstr,
                3
            );
            if (!$testStream) {
                $connected = false;
            } else {
                fclose($testStream);
            }
        }
        return [$logs, $connected];
    }
}
