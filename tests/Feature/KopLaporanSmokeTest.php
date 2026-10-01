<?php

namespace Tests\Feature;

use App\Helpers\CompanyProfile;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KopLaporanSmokeTest extends TestCase
{
    // Test memakai sqlite in-memory (lihat tests/CreatesApplication.php).
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disk publik di-fake supaya file logo asli milik pengguna tidak
        // ikut tersentuh oleh test ini.
        Storage::fake('public');
    }

    public function test_kop_laporan_menggunakan_profil_perusahaan(): void
    {
        $sebelum = CompanyProfile::all();

        try {
            Storage::disk('public')->put(
                CompanyProfile::LOGO_DIR.'/logo-test.svg',
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><circle cx="5" cy="5" r="5"/></svg>'
            );

            CompanyProfile::put([
                'nama' => 'PT. UJI LAPORAN',
                'alamat' => 'Jl. Kop No. 1',
                'telepon' => '021-123',
                'email' => 'kop@uji.test',
                'website' => 'uji.test',
                'nama_ttd' => 'Tester',
                'jabatan_ttd' => 'HRD',
                'logo' => CompanyProfile::LOGO_DIR.'/logo-test.svg',
            ]);

            $admin = User::create([
                'name' => 'Superadmin Uji',
                'username' => 'superadmin_kop',
                'email' => 'superadmin_kop@bbm.test',
                'password' => bcrypt('rahasia123'),
                'role' => 'superadmin',
            ]);
            $this->actingAs($admin);

            $karyawan = Karyawan::create([
                'id_karyawan' => '001',
                'nama' => 'Karyawan Uji',
            ]);
            $periode = ['tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => '2026-01-31'];

            $halaman = array_filter([
                '/absensi/cetak?'.http_build_query($periode + ['karyawan_id' => []]),
                '/laporan/kehadiran/cetak?'.http_build_query($periode + ['karyawan_id' => []]),
                // route memakai primary key karyawans.id, bukan id_karyawan (PIN)
                $karyawan
                    ? '/laporan/kehadiran/karyawan/'.$karyawan->id.'/cetak?'.http_build_query($periode)
                    : null,
            ]);

            foreach ($halaman as $url) {
                $html = $this->get($url)->assertOk()->getContent();

                $this->assertStringContainsString('PT. UJI LAPORAN', $html, "nama PT hilang di {$url}");
                $this->assertStringContainsString('Jl. Kop No. 1', $html, "alamat hilang di {$url}");
                $this->assertStringContainsString('/storage/'.CompanyProfile::LOGO_DIR.'/logo-test.svg', $html, "logo hilang di {$url}");
            }

            // laporan rekap punya blok TTD dari profil
            $rekap = $this->get('/laporan/kehadiran/cetak?'.http_build_query($periode + ['karyawan_id' => []]))
                ->assertOk()->getContent();
            $this->assertStringContainsString('Tester', $rekap);
            $this->assertStringContainsString('HRD', $rekap);
        } finally {
            CompanyProfile::put($sebelum);
        }
    }
}
