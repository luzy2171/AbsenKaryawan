<?php

use App\Helpers\CompanyProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed default profil perusahaan (nama PT, logo, data kop).
     */
    public function up(): void
    {
        $defaults = [
            'nama' => [
                'value' => CompanyProfile::DEFAULT_NAMA,
                'description' => 'Nama perusahaan / PT yang dipakai di sidebar dan seluruh kop laporan',
            ],
            'subtitle' => [
                'value' => CompanyProfile::DEFAULT_SUBTITLE,
                'description' => 'Teks kecil di bawah nama perusahaan pada sidebar',
            ],
            'logo' => [
                'value' => '',
                'description' => 'Path file logo perusahaan pada disk public',
            ],
            'alamat' => [
                'value' => '',
                'description' => 'Alamat perusahaan yang dicetak pada kop laporan',
            ],
            'telepon' => [
                'value' => '',
                'description' => 'Nomor telepon perusahaan pada kop laporan',
            ],
            'email' => [
                'value' => '',
                'description' => 'Email perusahaan pada kop laporan',
            ],
            'website' => [
                'value' => '',
                'description' => 'Website perusahaan pada kop laporan',
            ],
            'nama_ttd' => [
                'value' => '',
                'description' => 'Nama penanda tangan laporan pada blok TTD',
            ],
            'jabatan_ttd' => [
                'value' => 'Finance / HRD',
                'description' => 'Jabatan penanda tangan laporan pada blok TTD',
            ],
        ];

        foreach ($defaults as $key => $row) {
            // insertOrIgnore: kalau key sudah ada (mis. install ulang), data yang
            // sudah disunting superadmin tidak ditimpa.
            DB::table('settings')->insertOrIgnore([
                'key' => $key,
                'value' => $row['value'],
                'description' => $row['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->whereIn('key', CompanyProfile::KEYS)->delete();
    }
};
