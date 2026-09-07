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
        // Ambil data user dari masing-masing mesin
        $usersHik = $hikService->getAllUsers();
        $usersSol = $zkService->getAllUsers();
        
        $totalHik = count($usersHik);
        $totalSol = count($usersSol);
        
        // Ambil data user dari database lokal
        $karyawans = Karyawan::orderBy('id_karyawan')->get();
        
        return view('admin.mesin.index', compact('usersHik', 'usersSol', 'totalHik', 'totalSol', 'karyawans'));
    }

    public function tarikDataAll(Request $request, HikvisionService $hikService, ZktecoService $zkService)
    {
        $mesinType = $request->mesin_tujuan;
        $users = [];
        $mesinName = "";

        if ($mesinType == 'hikvision') {
            $users = $hikService->getAllUsers();
            $mesinName = "Hikvision";
        } elseif ($mesinType == 'solution') {
            $users = $zkService->getAllUsers();
            $mesinName = "Solution";
        } else {
            // Tarik semua jika all
            $usersHik = $hikService->getAllUsers();
            $usersSol = $zkService->getAllUsers();
            $users = array_merge((array)$usersHik, (array)$usersSol);
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
        
        $resHik = null;
        $resSol = null;

        if ($request->mesin_tujuan == 'hikvision' || $request->mesin_tujuan == 'all') {
            $resHik = $hikService->uploadNama($karyawan->id_karyawan, $karyawan->nama);
        }
        
        if ($request->mesin_tujuan == 'solution' || $request->mesin_tujuan == 'all') {
            $resSol = $zkService->uploadNama($karyawan->id_karyawan, $karyawan->nama);
        }

        $msg = [];
        if ($resHik) $msg[] = "Hikvision: $resHik";
        if ($resSol) $msg[] = "Solution: $resSol";

        return back()->with('status', "Proses pengiriman $karyawan->nama (PIN: $karyawan->id_karyawan). Hasil: " . implode(" | ", $msg));
    }

    public function hapusData($mesin, $pin, HikvisionService $hikService, ZktecoService $zkService)
    {
        $res = "Gagal";
        if ($mesin == 'hikvision') {
            $res = $hikService->hapusUser($pin);
        } else {
            $res = $zkService->hapusUser($pin);
        }

        if ($res == "Sukses") {
            return back()->with('status', "Berhasil menghapus PIN $pin dari $mesin.");
        } else {
            return back()->with('error', "Gagal menghapus PIN $pin dari $mesin: $res");
        }
    }
}
