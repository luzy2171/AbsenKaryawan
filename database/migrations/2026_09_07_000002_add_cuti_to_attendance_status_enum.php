<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ENUM hanya ada di MySQL. Pada sqlite (dipakai test) lewati saja.
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('Hadir', 'Terlambat', 'Alpha', 'Izin', 'Sakit', 'Cuti') DEFAULT 'Alpha'");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('Hadir', 'Terlambat', 'Alpha', 'Izin', 'Sakit') DEFAULT 'Alpha'");
    }
};
