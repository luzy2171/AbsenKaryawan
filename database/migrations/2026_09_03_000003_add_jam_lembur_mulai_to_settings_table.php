<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insert([
            [
                'key' => 'jam_lembur_mulai',
                'value' => '18:00',
                'description' => 'Jam mulai lembur karyawan (Format HH:MM)',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'jam_lembur_mulai')->delete();
    }
};