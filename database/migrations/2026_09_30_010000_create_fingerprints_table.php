<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Template sidik jari yang ditarik dari mesin absensi.
     *
     * Template disimpan sebagai base64 supaya aman di database dan tidak rusak
     * oleh karakter biner mentah.
     */
    public function up(): void
    {
        Schema::create('fingerprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawans')->cascadeOnDelete();
            $table->foreignId('machine_id')->nullable()->constrained('machine_status')->nullOnDelete();
            $table->unsignedSmallInteger('finger_id');       // 0..9, sesuai slot jari di mesin
            $table->longText('template');                    // base64 dari mesin
            $table->unsignedInteger('size')->default(0);     // panjang template asli (bukan base64)
            $table->timestamps();

            $table->unique(['machine_id', 'karyawan_id', 'finger_id'], 'fingerprints_mesin_karyawan_jari');
            $table->index(['karyawan_id', 'finger_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fingerprints');
    }
};
