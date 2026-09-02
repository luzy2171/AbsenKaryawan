<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\AuditLogger;

class SettingController extends Controller
{
    public function index()
    {
        $settings = DB::table('settings')->pluck('value', 'key');

        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'jam_masuk' => 'required',
            'jam_pulang' => 'required',
            'toleransi_terlambat' => 'required|numeric|min:0',
            'auto_pull_interval' => 'required|numeric|min:1',
            'jam_lembur_mulai' => 'required|date_format:H:i',
        ]);

        // Get old values for audit
        $oldSettings = DB::table('settings')->pluck('value', 'key')->toArray();

        DB::table('settings')->where('key', 'jam_masuk')->update(['value' => $request->jam_masuk, 'updated_at' => now()]);
        DB::table('settings')->where('key', 'jam_pulang')->update(['value' => $request->jam_pulang, 'updated_at' => now()]);
        DB::table('settings')->where('key', 'toleransi_terlambat')->update(['value' => $request->toleransi_terlambat, 'updated_at' => now()]);
        DB::table('settings')->where('key', 'auto_pull_interval')->update(['value' => $request->auto_pull_interval, 'updated_at' => now()]);
        DB::table('settings')->where('key', 'jam_lembur_mulai')->update(['value' => $request->jam_lembur_mulai, 'updated_at' => now()]);

        // Log audit
        AuditLogger::settingsUpdated($oldSettings, [
            'jam_masuk' => $request->jam_masuk,
            'jam_pulang' => $request->jam_pulang,
            'toleransi_terlambat' => $request->toleransi_terlambat,
            'auto_pull_interval' => $request->auto_pull_interval,
            'jam_lembur_mulai' => $request->jam_lembur_mulai,
        ]);

        return redirect()->back()->with('success', 'Pengaturan berhasil diperbarui!');
    }
}