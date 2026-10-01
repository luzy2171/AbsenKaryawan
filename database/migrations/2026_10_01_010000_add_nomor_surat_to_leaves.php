<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            // Nomor surat dicetak di kop. Kosongkan bila mau memakai format
            // otomatis (nomor urut pengajuan / jenis / bulan).
            $table->string('nomor_surat', 100)->nullable()->after('karyawan_id');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn('nomor_surat');
        });
    }
};
