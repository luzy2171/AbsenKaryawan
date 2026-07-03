<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Services\AbsensiService;

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
    public function store(Request $request, AbsensiService $absensiService)
    {
        // 1. Validasi Input Form
        $request->validate([
            'id_karyawan' => 'required|unique:karyawans,id_karyawan',
            'nama'        => 'required|string|max:255',
            'departemen'  => 'nullable|string',
            'jabatan'     => 'nullable|string',
        ]);

        // 2. Simpan ke database internal website
        Karyawan::create([
            'id_karyawan' => $request->id_karyawan,
            'nama'        => $request->nama,
            'departemen'  => $request->departemen,
            'jabatan'     => $request->jabatan,
            'status'      => 'Aktif'
        ]);

        // 3. Otomatis kirim data nama dan PIN ke mesin absensi fisik via SOAP Service
        $absensiService->uploadNama($request->id_karyawan, $request->nama);

        return redirect()->route('karyawan.index')->with('status', 'Karyawan berhasil ditambahkan ke Web dan Mesin Absensi.');
    }

    /**
     * FITUR BARU: Sinkronisasi Otomatis Semua User dari Perangkat ke Database Web
     */
    public function syncDariMesin(AbsensiService $absensiService)
    {
        // 1. Ambil seluruh data user yang ada di memori mesin
        $usersDariMesin = $absensiService->getAllUsers();

        if (empty($usersDariMesin)) {
            return back()->with('error', 'Gagal mengambil data dari mesin. Pastikan mesin dalam kondisi terhubung (Online).');
        }

        $karyawanBaru = 0;

        // 2. Lakukan pengecekan dan penyimpanan data ke DB Web secara massal
        foreach ($usersDariMesin as $user) {
            // Cek apakah ID Karyawan (PIN) sudah terdaftar di website
            $exists = Karyawan::where('id_karyawan', $user['pin'])->exists();

            if (!$exists) {
                Karyawan::create([
                    'id_karyawan' => $user['pin'],
                    'nama'        => $user['name'],
                    'departemen'  => '-',
                    'jabatan'     => 'Staf',
                    'status'      => 'Aktif'
                ]);
                $karyawanBaru++;
            }
        }

        return redirect()->route('karyawan.index')->with('status', "Sinkronisasi berhasil! Memproses $karyawanBaru data karyawan baru dari mesin absensi.");
    }

    /**
     * Menghapus karyawan dari Web dan Mesin Absensi Fisik
     */
    public function destroy($id, AbsensiService $absensiService)
    {
        $karyawan = Karyawan::findOrFail($id);

        // 1. Hapus user dari mesin absensi fisik berdasarkan PIN/ID-nya
        $absensiService->hapusUser($karyawan->id_karyawan);

        // 2. Hapus dari database website
        $karyawan->delete();

        return back()->with('status', 'Data karyawan di web dan mesin fisik berhasil dihapus.');
    }
}
