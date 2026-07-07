<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Tambahkan ini untuk menggunakan Query Builder

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Tempat menyimpan nama pengaturan (ex: jam_masuk)
            $table->string('value');         // Tempat menyimpan nilai pengaturan (ex: 08:00)
            $table->string('description')->nullable(); // Penjelasan fungsi pengaturan
            $table->timestamps();
        });

        // Menyisipkan data default langsung saat migrasi dijalankan
        DB::table('settings')->insert([
            [
                'key' => 'jam_masuk',
                'value' => '09:00',
                'description' => 'Jam standar masuk kerja karyawan (Format HH:MM)',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'jam_pulang',
                'value' => '18:00',
                'description' => 'Jam standar pulang kerja karyawan (Format HH:MM)',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'toleransi_terlambat',
                'value' => '120',
                'description' => 'Batas toleransi keterlambatan dalam hitungan menit',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
