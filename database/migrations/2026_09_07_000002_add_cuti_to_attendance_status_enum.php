<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('Hadir', 'Terlambat', 'Alpha', 'Izin', 'Sakit', 'Cuti') DEFAULT 'Alpha'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('Hadir', 'Terlambat', 'Alpha', 'Izin', 'Sakit') DEFAULT 'Alpha'");
    }
};