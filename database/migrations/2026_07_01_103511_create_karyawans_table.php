<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::create('karyawans', function (Blueprint $table) {
        $table->id();
        $table->string('id_karyawan')->unique(); // Ini untuk mapping PIN dari mesin (contoh: 001, 002)
        $table->string('nama');
        $table->string('departemen')->nullable();
        $table->string('jabatan')->nullable();
        $table->enum('status', ['Aktif', 'Nonaktif'])->default('Aktif');
        $table->timestamps();
    });
}
};
