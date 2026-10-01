<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bersihkan sisa duplikat lebih dulu, karena unique index akan gagal
        // kalau masih ada baris kembar. Yang dipertahankan baris paling lengkap
        // (punya jam_pulang); tie-break id terkecil.
        $kembar = DB::table('attendances')
            ->selectRaw('karyawan_id, tanggal, group_concat(id ORDER BY id) ids')
            ->groupBy('karyawan_id', 'tanggal')
            ->havingRaw('count(*) > 1')
            ->get();

        $terhapus = 0;
        foreach ($kembar as $grup) {
            $baris = DB::table('attendances')
                ->whereIn('id', array_map('intval', explode(',', $grup->ids)))
                ->get()
                ->sortByDesc(fn ($b) => [$b->jam_pulang !== null ? 1 : 0, -$b->id])
                ->values();

            $idsHapus = $baris->slice(1)->pluck('id');
            if ($idsHapus->isNotEmpty()) {
                $terhapus += DB::table('attendances')->whereIn('id', $idsHapus)->delete();
            }
        }

        if ($terhapus > 0) {
            Log::warning("absensi: $terhapus baris duplikat dihapus saat menambah unique index.");
        }

        Schema::table('attendances', function (Blueprint $table) {
            // jaminan terakhir: satu karyawan hanya punya satu baris per tanggal,
            // sehingga dua proses pull paralel tidak bisa membuat baris kembar.
            $table->unique(['karyawan_id', 'tanggal'], 'attendances_karyawan_tanggal_unique');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendances_karyawan_tanggal_unique');
        });
    }
};
