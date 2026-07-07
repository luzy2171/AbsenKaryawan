<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    /**
     * Menampilkan Halaman Pengaturan Jam Kerja
     */
    public function index()
    {
        // Mengambil data settings dan mengubahnya jadi format key => value (Contoh: 'jam_masuk' => '08:00')
        $settings = DB::table('settings')->pluck('value', 'key');

        return view('admin.settings', compact('settings'));
    }

    /**
     * Menyimpan Perubahan dari Form Interface
     */
    public function update(Request $request)
    {
        // Validasi inputan dari form web
        $request->validate([
            'jam_masuk' => 'required',
            'jam_pulang' => 'required',
            'toleransi_terlambat' => 'required|numeric|min:0',
        ]);

        // Update data satu per satu ke database MySQL
        DB::table('settings')->where('key', 'jam_masuk')->update(['value' => $request->jam_masuk, 'updated_at' => now()]);
        DB::table('settings')->where('key', 'jam_pulang')->update(['value' => $request->jam_pulang, 'updated_at' => now()]);
        DB::table('settings')->where('key', 'toleransi_terlambat')->update(['value' => $request->toleransi_terlambat, 'updated_at' => now()]);

        // Kembalikan ke halaman sebelumya dengan pesan sukses
        return redirect()->back()->with('success', 'Pengaturan waktu kerja berhasil diperbarui!');
    }
}
