<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HikvisionService;
use App\Services\ZktecoService;
use App\Models\Karyawan;

class MesinAbsensiController extends Controller
{
    public function index()
    {
        // Default redirect to hikvision or overview
        return redirect()->route('admin.mesin.hikvision');
    }

    public function hikvision(HikvisionService $hikService)
    {
        $mesinType = 'hikvision';
        $mesinName = 'Mesin HIKVISION';
        
        // Ambil data user dari mesin
        $usersMesin = $hikService->getAllUsers();
        $totalMesin = count($usersMesin);
        
        // Ambil data user dari database lokal
        $karyawans = Karyawan::orderBy('id_karyawan')->get();
        
        return view('admin.mesin.detail', compact('mesinType', 'mesinName', 'usersMesin', 'totalMesin', 'karyawans'));
    }

    public function solution(ZktecoService $zkService)
    {
        $mesinType = 'solution';
        $mesinName = 'Mesin SOLUTION X100-C';
        
        // Ambil data user dari mesin
        $usersMesin = $zkService->getAllUsers();
        $totalMesin = count($usersMesin);
        
        // Ambil data user dari database lokal
        $karyawans = Karyawan::orderBy('id_karyawan')->get();
        
        return view('admin.mesin.detail', compact('mesinType', 'mesinName', 'usersMesin', 'totalMesin', 'karyawans'));
    }

    public function tarikData($mesin, Request $request, HikvisionService $hikService, ZktecoService $zkService)
    {
        $users = [];
        if ($mesin == 'hikvision') {
            $users = $hikService->getAllUsers();
        } else {
            $users = $zkService->getAllUsers();
        }

        $berhasil = 0;
        foreach ($users as $u) {
            // Cek apakah sudah ada di DB lokal
            $exist = Karyawan::where('id_karyawan', $u['pin'])->first();
            if (!$exist) {
                Karyawan::create([
                    'id_karyawan' => $u['pin'],
                    'nama' => $u['name'],
                    'status' => 'Aktif'
                ]);
                $berhasil++;
            }
        }

        return back()->with('status', "Berhasil menarik $berhasil data pengguna baru dari $mesin ke database lokal.");
    }

    public function kirimData($mesin, Request $request, HikvisionService $hikService, ZktecoService $zkService)
    {
        $request->validate([
            'karyawan_id' => 'required'
        ]);

        $karyawan = Karyawan::findOrFail($request->karyawan_id);
        
        $res = "Gagal";
        if ($mesin == 'hikvision') {
            $res = $hikService->uploadNama($karyawan->id_karyawan, $karyawan->nama);
        } else {
            $res = $zkService->uploadNama($karyawan->id_karyawan, $karyawan->nama);
        }

        if ($res == "Sukses") {
            return back()->with('status', "Berhasil mengirim $karyawan->nama (PIN: $karyawan->id_karyawan) ke $mesin.");
        } else {
            return back()->with('error', "Gagal mengirim data ke $mesin: $res");
        }
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
