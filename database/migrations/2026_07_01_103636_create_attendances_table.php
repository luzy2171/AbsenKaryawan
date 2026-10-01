<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel karyawan
            $table->foreignId('karyawan_id')->constrained('karyawans')->onDelete('cascade');
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            // 'Cuti' perlu ada sejak awal: pada sqlite (dipakai test) migration
            // penambahan enum dilewati, jadi kalau tidak ikut di sini test akan
            // gagal dengan "CHECK constraint failed: status".
            $table->enum('status', ['Hadir', 'Terlambat', 'Alpha', 'Izin', 'Sakit', 'Cuti'])->default('Alpha');
            $table->string('verifikasi')->nullable(); // Contoh: 'Sidik Jari', 'Wajah', 'Password'
            $table->timestamps();
        });
    }
};
