<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'jam_masuk' => 'required|date_format:H:i',
            'jam_pulang' => 'required|date_format:H:i',
            'toleransi_terlambat' => 'required|integer|min:0|max:1440',
            'auto_pull_interval' => 'required|integer|min:1|max:24',
            'jam_lembur_mulai' => 'required|date_format:H:i',
            'required_approvals' => 'required|integer|min:1|max:10',
        ]);

        // Get old values for audit
        $oldSettings = DB::table('settings')->pluck('value', 'key')->toArray();

        DB::transaction(function () use ($request) {
            DB::table('settings')->updateOrInsert(['key' => 'jam_masuk'], ['value' => $request->jam_masuk, 'updated_at' => now()]);
            DB::table('settings')->updateOrInsert(['key' => 'jam_pulang'], ['value' => $request->jam_pulang, 'updated_at' => now()]);
            DB::table('settings')->updateOrInsert(['key' => 'toleransi_terlambat'], ['value' => $request->toleransi_terlambat, 'updated_at' => now()]);
            DB::table('settings')->updateOrInsert(['key' => 'auto_pull_interval'], ['value' => $request->auto_pull_interval, 'updated_at' => now()]);
            DB::table('settings')->updateOrInsert(['key' => 'jam_lembur_mulai'], ['value' => $request->jam_lembur_mulai, 'updated_at' => now()]);
            DB::table('settings')->updateOrInsert(['key' => 'required_approvals'], ['value' => $request->required_approvals, 'updated_at' => now()]);
        });

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
