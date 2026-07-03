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
        $table->enum('status', ['Hadir', 'Terlambat', 'Alpha', 'Izin', 'Sakit'])->default('Alpha');
        $table->string('verifikasi')->nullable(); // Contoh: 'Sidik Jari', 'Wajah', 'Password'
        $table->timestamps();
    });
}
};
