<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HikvisionService;
use App\Services\ZktecoService;
use App\Models\Karyawan;

class MesinAbsensiController extends Controller
{
    public function index(HikvisionService $hikService, ZktecoService $zkService)
    {
        $machines = \App\Models\MachineStatus::all();

        // Ambil data user dari masing-masing mesin (untuk tabel bawah)
        $usersHik = [];
        $usersSol = [];
        
        foreach ($machines as $m) {
            if ($m->machine_type == 'hikvision') {
                $hikService->setConnection($m->machine_ip, $m->username, $m->password, $m->port);
                $usersHik = array_merge($usersHik, $hikService->getAllUsers());
            } elseif ($m->machine_type == 'solution') {
                $zkService->setConnection($m->machine_ip, $m->port);
                $usersSol = array_merge($usersSol, $zkService->getAllUsers());
            }
        }
        
        $totalHik = count($usersHik);
        $totalSol = count($usersSol);
        
        // Ambil data user dari database lokal
        $karyawans = Karyawan::orderBy('id_karyawan')->get();
        
        return view('admin.mesin.index', compact('machines', 'usersHik', 'usersSol', 'totalHik', 'totalSol', 'karyawans'));
    }

    public function tarikDataAll(Request $request, HikvisionService $hikService, ZktecoService $zkService)
    {
        $mesinType = $request->mesin_tujuan;
        $users = [];
        $mesinName = "";
        
        $machines = \App\Models\MachineStatus::all();

        if ($mesinType == 'hikvision') {
            foreach ($machines->where('machine_type', 'hikvision') as $m) {
                $hikService->setConnection($m->machine_ip, $m->username, $m->password, $m->port);
                $users = array_merge($users, (array)$hikService->getAllUsers());
            }
            $mesinName = "Hikvision";
        } elseif ($mesinType == 'solution') {
            foreach ($machines->where('machine_type', 'solution') as $m) {
                $zkService->setConnection($m->machine_ip, $m->port);
                $users = array_merge($users, (array)$zkService->getAllUsers());
            }
            $mesinName = "Solution";
        } else {
            // Tarik semua jika all
            foreach ($machines as $m) {
                if ($m->machine_type == 'hikvision') {
                    $hikService->setConnection($m->machine_ip, $m->username, $m->password, $m->port);
                    $users = array_merge($users, (array)$hikService->getAllUsers());
                } else {
                    $zkService->setConnection($m->machine_ip, $m->port);
                    $users = array_merge($users, (array)$zkService->getAllUsers());
                }
            }
            $mesinName = "Semua Vendor";
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

    public function kirimData(Request $request, HikvisionService $hikService, ZktecoService $zkService)
    {
        $request->validate([
            'karyawan_id' => 'required',
            'mesin_tujuan' => 'required|in:hikvision,solution,all'
        ]);

        $karyawan = Karyawan::findOrFail($request->karyawan_id);
        $machines = \App\Models\MachineStatus::all();
        $msg = [];

        if ($request->mesin_tujuan == 'hikvision' || $request->mesin_tujuan == 'all') {
            foreach ($machines->where('machine_type', 'hikvision') as $m) {
                $hikService->setConnection($m->machine_ip, $m->username, $m->password, $m->port);
                $resHik = $hikService->uploadNama($karyawan->id_karyawan, $karyawan->nama);
                $msg[] = "Hikvision ({$m->machine_name}): $resHik";
            }
        }
        
        if ($request->mesin_tujuan == 'solution' || $request->mesin_tujuan == 'all') {
            foreach ($machines->where('machine_type', 'solution') as $m) {
                $zkService->setConnection($m->machine_ip, $m->port);
                $resSol = $zkService->uploadNama($karyawan->id_karyawan, $karyawan->nama);
                $msg[] = "Solution ({$m->machine_name}): $resSol";
            }
        }

        return back()->with('status', "Proses pengiriman $karyawan->nama (PIN: $karyawan->id_karyawan). Hasil: " . implode(" | ", $msg));
    }

    public function hapusData($mesin, $pin, HikvisionService $hikService, ZktecoService $zkService)
    {
        $res = "Gagal";
        $machines = \App\Models\MachineStatus::where('machine_type', $mesin)->get();
        $msg = [];
        
        foreach ($machines as $m) {
            if ($mesin == 'hikvision') {
                $hikService->setConnection($m->machine_ip, $m->username, $m->password, $m->port);
                $r = $hikService->hapusUser($pin);
            } else {
                $zkService->setConnection($m->machine_ip, $m->port);
                $r = $zkService->hapusUser($pin);
            }
            $msg[] = "{$m->machine_name}: $r";
        }

        return back()->with('status', "Berhasil menghapus PIN $pin dari $mesin. Detail: " . implode(" | ", $msg));
    }

    public function storeDevice(Request $request)
    {
        $request->validate([
            'machine_ip' => 'required|ip|unique:machine_status,machine_ip',
            'machine_name' => 'required|string|max:255',
            'machine_type' => 'required|in:hikvision,solution',
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
    
    public function destroyDevice($id)
    {
        $machine = \App\Models\MachineStatus::findOrFail($id);
        $machine->delete();
        return redirect()->back()->with('status', "Device berhasil dihapus.");
    }

    public function pingDevice($id, HikvisionService $hikService, ZktecoService $zkService)
    {
        $machine = \App\Models\MachineStatus::findOrFail($id);
        $startTime = microtime(true);
        
        $isOnline = false;
        if ($machine->machine_type == 'hikvision') {
            $hikService->setConnection($machine->machine_ip, $machine->username, $machine->password, $machine->port);
            $response = $hikService->request('/ISAPI/System/deviceInfo?format=json');
            if ($response['http_code'] == 200 || $response['http_code'] == 401) {
                $isOnline = true;
            }
        } else {
            $zkService->setConnection($machine->machine_ip, $machine->port);
            if ($zkService->connect()) {
                $isOnline = true;
            }
        }
        
        $responseTime = round((microtime(true) - $startTime) * 1000);
        $machine->updateStatus($isOnline, $responseTime);
        
        if ($isOnline) {
            return redirect()->back()->with('status', "Ping berhasil! {$machine->machine_name} Online (Response: {$responseTime}ms).");
        } else {
            return redirect()->back()->with('error', "Ping gagal! {$machine->machine_name} Offline.");
        }
    }
}
