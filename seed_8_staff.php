<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\App\Models\Karyawan::truncate();

$staff = [
    ['id_karyawan' => '1', 'nama' => 'Baim', 'departemen' => '-', 'jabatan' => 'Staf', 'jatah_cuti_tahunan' => 12, 'status' => 'Aktif'],
    ['id_karyawan' => '3', 'nama' => 'Linda', 'departemen' => '-', 'jabatan' => 'Staf', 'jatah_cuti_tahunan' => 12, 'status' => 'Aktif'],
    ['id_karyawan' => '4', 'nama' => 'Dewi', 'departemen' => '-', 'jabatan' => 'Staf', 'jatah_cuti_tahunan' => 12, 'status' => 'Aktif'],
    ['id_karyawan' => '5', 'nama' => 'Vilysia', 'departemen' => '-', 'jabatan' => 'Staf', 'jatah_cuti_tahunan' => 12, 'status' => 'Aktif'],
    ['id_karyawan' => '6', 'nama' => 'Nur', 'departemen' => '-', 'jabatan' => 'Staf', 'jatah_cuti_tahunan' => 12, 'status' => 'Aktif'],
    ['id_karyawan' => '7', 'nama' => 'Sita', 'departemen' => '-', 'jabatan' => 'Staf', 'jatah_cuti_tahunan' => 12, 'status' => 'Aktif'],
    ['id_karyawan' => '8', 'nama' => 'Ari', 'departemen' => '-', 'jabatan' => 'Staf', 'jatah_cuti_tahunan' => 12, 'status' => 'Aktif'],
    ['id_karyawan' => '9', 'nama' => 'Ali', 'departemen' => '-', 'jabatan' => 'Staf', 'jatah_cuti_tahunan' => 12, 'status' => 'Aktif'],
];

foreach ($staff as $s) {
    \App\Models\Karyawan::create($s);
}
echo "8 Staff restored.";
