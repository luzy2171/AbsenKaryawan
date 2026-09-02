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
                'key' => 'auto_pull_interval',
                'value' => '24',
                'description' => 'Interval tarik data otomatis dalam jam (contoh: 1 = setiap 1 jam, 2 = setiap 2 jam, 24 = sekali sehari)',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'auto_pull_interval')->delete();
    }
};