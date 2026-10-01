<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalamanUtamaSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pastikan brand sidebar + menu Profil Perusahaan muncul di semua halaman.
     */
    public function test_semua_halaman_admin_tampil_dengan_brand_perusahaan(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $admin = User::create([
            'name' => 'Superadmin Uji',
            'username' => 'superadmin_uji',
            'email' => 'superadmin_uji@bbm.test',
            'password' => bcrypt('rahasia123'),
            'role' => 'superadmin',
        ]);
        $this->actingAs($admin);

        $halaman = [
            '/dashboard',
            '/karyawan',
            '/absensi',
            '/laporan/kehadiran',
            '/admin/settings',
            '/admin/perusahaan',
            '/pengaturan',
            '/admin/leaves',
            '/admin/cuti-control',
            '/admin/users',
            '/admin/maintenance',
            '/admin/audit-logs',
        ];

        foreach ($halaman as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('brand-block', $html, "brand sidebar hilang di {$url}");
            $this->assertStringContainsString('/admin/perusahaan', $html, "menu Profil Perusahaan hilang di {$url}");
        }
    }
}
