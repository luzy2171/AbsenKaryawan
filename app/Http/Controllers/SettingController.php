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
            'required_approvals' => 'required|numeric|min:1',
        ]);

        // Get old values for audit
        $oldSettings = DB::table('settings')->pluck('value', 'key')->toArray();

        DB::table('settings')->updateOrInsert(['key' => 'jam_masuk'], ['value' => $request->jam_masuk, 'updated_at' => now()]);
        DB::table('settings')->updateOrInsert(['key' => 'jam_pulang'], ['value' => $request->jam_pulang, 'updated_at' => now()]);
        DB::table('settings')->updateOrInsert(['key' => 'toleransi_terlambat'], ['value' => $request->toleransi_terlambat, 'updated_at' => now()]);
        DB::table('settings')->updateOrInsert(['key' => 'auto_pull_interval'], ['value' => $request->auto_pull_interval, 'updated_at' => now()]);
        DB::table('settings')->updateOrInsert(['key' => 'jam_lembur_mulai'], ['value' => $request->jam_lembur_mulai, 'updated_at' => now()]);
        DB::table('settings')->updateOrInsert(['key' => 'required_approvals'], ['value' => $request->required_approvals, 'updated_at' => now()]);

        // Log audit
        AuditLogger::settingsUpdated($oldSettings, [
            'jam_masuk' => $request->jam_masuk,
            'jam_pulang' => $request->jam_pulang,
            'toleransi_terlambat' => $request->toleransi_terlambat,
            'auto_pull_interval' => $request->auto_pull_interval,
            'jam_lembur_mulai' => $request->jam_lembur_mulai,
            'required_approvals' => $request->required_approvals,
        ]);

        return redirect()->back()->with('success', 'Pengaturan berhasil diperbarui!');
    }
}