<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ZktecoService;
use App\Models\Karyawan;

class MesinAbsensiController extends Controller
{
    public function index(ZktecoService $zkService)
    {
        $machines = \App\Models\MachineStatus::all();

        // Ambil data user dari mesin Solution
        $usersSol = [];
        
        foreach ($machines as $m) {
            $zkService->setConnection($m->machine_ip, $m->port);
            $usersSol = array_merge($usersSol, $zkService->getAllUsers());
        }
        
        $totalSol = count($usersSol);
        
        // Ambil data user dari database lokal
        $karyawans = Karyawan::orderBy('id_karyawan')->get();
        
        return view('admin.mesin.index', compact('machines', 'usersSol', 'totalSol', 'karyawans'));
    }

    public function tarikDataAll(Request $request, ZktecoService $zkService)
    {
        $mesinType = $request->mesin_tujuan;
        $users = [];
        $mesinName = "";
        
        $machines = \App\Models\MachineStatus::all();

        foreach ($machines as $m) {
            $zkService->setConnection($m->machine_ip, $m->port);
            $users = array_merge($users, (array)$zkService->getAllUsers());
            $mesinName = "Solution";
        }

        $berhasil = 0;
        foreach ($users as $u) {
            // Cek apakah sudah ada di DB lokal berdasarkan ID/PIN
            if (isset($u['pin']) && $u['pin'] != '') {
                $exist = Karyawan::where('id_karyawan', $u['pin'])->first();
                if (!$exist) {
                    Karyawan::create([
                        'id_karyawan' => $u['pin'],
                        'nama' => $u['name'] ?: 'User ' . $u['pin'],
                        'status' => 'Aktif'
                    ]);
                    $berhasil++;
                }
            }
        }

        return back()->with('status', "Berhasil menarik $berhasil data pengguna baru dari $mesinName ke database lokal.");
    }

    public function kirimData(Request $request, ZktecoService $zkService)
    {
        $request->validate([
            'karyawan_id' => 'required',
            'mesin_tujuan' => 'required|in:solution,all'
        ]);

        $karyawan = Karyawan::findOrFail($request->karyawan_id);
        $machines = \App\Models\MachineStatus::all();
        $msg = [];

        foreach ($machines as $m) {
            $zkService->setConnection($m->machine_ip, $m->port);
            $resSol = $zkService->uploadNama($karyawan->id_karyawan, $karyawan->nama);
            $msg[] = "Solution ({$m->machine_name}): $resSol";
        }

        return back()->with('status', "Proses pengiriman $karyawan->nama (PIN: $karyawan->id_karyawan). Hasil: " . implode(" | ", $msg));
    }

    public function hapusData($mesin, $pin, ZktecoService $zkService)
    {
        $res = "Gagal";
        $machines = \App\Models\MachineStatus::all();
        $msg = [];
        
        foreach ($machines as $m) {
            $zkService->setConnection($m->machine_ip, $m->port);
            $r = $zkService->hapusUser($pin);
            $msg[] = "{$m->machine_name}: $r";
        }

        return back()->with('status', "Berhasil menghapus PIN $pin dari mesin. Detail: " . implode(" | ", $msg));
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

    public function cleanUnsynced($mesin, ZktecoService $zkService)
    {
        $machines = \App\Models\MachineStatus::all();
        $localKaryawans = Karyawan::pluck('id_karyawan')->toArray();
        $deletedCount = 0;
        $failedCount = 0;

        foreach ($machines as $m) {
            $zkService->setConnection($m->machine_ip, $m->port);
            $users = $zkService->getAllUsers();
            foreach ($users as $u) {
                if (!in_array((string)$u['pin'], $localKaryawans)) {
                    $res = $zkService->hapusUser($u['pin']);
                    if ($res == "Sukses") $deletedCount++;
                    else $failedCount++;
                }
            }
        }

        return back()->with('status', "Pembersihan selesai! $deletedCount data asing berhasil dihapus dari memori mesin Solution." . ($failedCount > 0 ? " ($failedCount gagal)" : ""));
    }

    public function storeDevice(Request $request)
    {
        $request->validate([
            'machine_ip' => 'required|ip|unique:machine_status,machine_ip',
            'machine_name' => 'required|string|max:255',
            'machine_type' => 'required|in:solution',
            'port' => 'required|numeric',
        ]);

        \App\Models\MachineStatus::create([
            'machine_ip' => $request->machine_ip,
            'machine_name' => $request->machine_name,
            'machine_type' => $request->machine_type,
            'port' => $request->port,
            'username' => $request->username,
            'password' => $request->password,
            'status' => 'offline',
        ]);

        return redirect()->back()->with('status', "Device {$request->machine_name} berhasil ditambahkan!");
    }
    
    public function updateDevice(Request $request, $id)
    {
        $machine = \App\Models\MachineStatus::findOrFail($id);
        
        $request->validate([
            'machine_ip' => 'required|ip|unique:machine_status,machine_ip,'.$id,
            'machine_name' => 'required|string|max:255',
            'machine_type' => 'required|in:solution',
            'port' => 'required|numeric',
        ]);

        $updateData = [
            'machine_ip' => $request->machine_ip,
            'machine_name' => $request->machine_name,
            'machine_type' => $request->machine_type,
            'port' => $request->port,
            'username' => $request->username,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = $request->password;
        }

        $machine->update($updateData);

        return redirect()->back()->with('status', "Device {$request->machine_name} berhasil diperbarui!");
    }
    
    public function destroyDevice($id)
    {
        $machine = \App\Models\MachineStatus::findOrFail($id);
        $machine->delete();
        return redirect()->back()->with('status', "Device berhasil dihapus.");
    }

    public function pingDevice($id, ZktecoService $zkService)
    {
        $machine = \App\Models\MachineStatus::findOrFail($id);
        $startTime = microtime(true);
        
        $zkService->setConnection($machine->machine_ip, $machine->port);
        $isOnline = false;
        if ($zkService->connect()) {
            $isOnline = true;
            $zkService->connect()->disconnect();
        }
        
        $responseTime = round((microtime(true) - $startTime) * 1000);
        $machine->updateStatus($isOnline, $responseTime);
        
        if ($isOnline) {
            return redirect()->back()->with('status', "Ping berhasil! {$machine->machine_name} Online (Response: {$responseTime}ms).");
        } else {
            return redirect()->back()->with('error', "Ping gagal! {$machine->machine_name} Offline.");
        }
    }
