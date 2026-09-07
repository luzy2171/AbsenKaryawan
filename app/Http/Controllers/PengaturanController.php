<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ZktecoService;
use App\Helpers\AuditLogger;
use App\Models\MachineStatus;

class PengaturanController extends Controller
{
    /**
     * Mengakses Halaman Dashboard Menu Pengaturan Alat
     */
    public function index(ZktecoService $zkService, Request $request)
    {
        // Ambil semua machine status
        $machineStatuses = MachineStatus::all();
        $primaryMachine = MachineStatus::first();
        
        $users = [];
        $logs = [];
        $templates = [];
        $currentMachine = $primaryMachine;

        // Auto load users if nothing is requested but we want to show it as default
        // So that "Data Karyawan" is always visible without needing to press "Tarik Data"
        $shouldViewUsers = $request->has('view_users') || (!$request->has('view_logs') && !$request->has('download_fp'));

        // Fitur 1: Aksi tampilkan list User dari Mesin (Otomatis load)
        if ($shouldViewUsers) {
            $startTime = microtime(true);
            $users = $zkService->getAllUsers();
            
            // Update status mesin
            if ($primaryMachine) {
                $responseTime = round((microtime(true) - $startTime) * 1000);
                $primaryMachine->updateStatus(!empty($users), $responseTime);
            }
        }

        // Fitur 2: Aksi tampilkan Log Mentah Gabungan Nama dari Mesin
        if ($request->has('view_logs')) {
            $startTime = microtime(true);
            $logs = $zkService->downloadLogTigaBulan();
            
            // Update status mesin
            if ($primaryMachine) {
                $responseTime = round((microtime(true) - $startTime) * 1000);
                $primaryMachine->updateStatus(!empty($logs), $responseTime);
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
        ]);

        $machine = MachineStatus::create([
            'machine_ip' => $request->machine_ip,
            'machine_name' => $request->machine_name,
            'status' => 'offline',
        ]);

        // Log audit
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

        // Log audit
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
        $connection = @fsockopen($machine->machine_ip, 80, $errno, $errstr, 3);
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
        // Reset semua mesin ke non-default
        MachineStatus::query()->update(['is_default' => false]);
        
        // Set selected machine sebagai default
        $machine = MachineStatus::findOrFail($id);
        $machine->update(['is_default' => true]);

        return redirect()->back()->with('status', "Mesin {$machine->machine_name} berhasil dijadikan default!");
    }

    /**
     * Fitur 4: Proses Kosongkan Log Transaksi Mesin Absensi
     */
    public function clearMachineLogs(ZktecoService $zkService)
    {
        $result = $zkService->clearLogData();

        if ($result === "Koneksi Gagal") {
            return back()->with('error', 'Gagal terhubung dengan mesin absensi.');
        }

        // Log audit
        AuditLogger::machineClearLog();

        return back()->with('status', 'Log transaksi mesin berhasil dibersihkan! Respon Alat: ' . $result);
    }

    /**
     * Fitur 5: Proses Hapus User Langsung dari Menu Pengaturan
     */
    public function hapusUserDariMesin(Request $request, ZktecoService $zkService)
    {
        $request->validate([
            'user_id' => 'required'
        ]);

        $result = $zkService->hapusUser($request->input('user_id'));

        if ($result === "Koneksi Gagal") {
            return back()->with('error', 'Gagal terhubung dengan mesin absensi.');
        }

        // Log audit
        AuditLogger::machineUserDeleted($request->input('user_id'));

        return back()->with('status', 'Proses Hapus User Berhasil! Respon Alat: ' . $result);
    }

    /**
     * Fitur 6: Memproses Sinkronisasi Waktu Server ke Perangkat Absensi Fisik
     */
    public function synchronizeDeviceTime(ZktecoService $zkService)
    {
        $result = $zkService->syncTime();

        if ($result === "Koneksi Gagal") {
            return back()->with('error', 'Gagal menyamakan waktu. Koneksi ke mesin terputus.');
        }

        // Log audit
        AuditLogger::machineSync();

        return back()->with('status', 'Waktu mesin berhasil disinkronkan dengan server web! Respon: ' . $result);
    }

    /**
     * Fitur 7: Memproses Perintah Restart Mesin Absensi Fisik
     */
    public function restartMachine(ZktecoService $zkService)
    {
        $result = $zkService->restartDevice();

        if ($result === "Koneksi Gagal") {
            return back()->with('error', 'Gagal merestart perangkat. Koneksi ke mesin terputus.');
        }

        // Log audit
        AuditLogger::machineRestart();

        return back()->with('status', 'Perintah restart berhasil dikirim! Mesin absensi sedang memuat ulang. Respon: ' . $result);
    }

    /**
     * Fitur 8: Memproses Upload Template Sidik Jari secara Manual via Pengaturan
     */
    }
