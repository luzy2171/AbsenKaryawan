<?php

namespace Tests\Feature;

use App\Helpers\CompanyProfile;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyProfileSmokeTest extends TestCase
{
    // Test memakai sqlite in-memory (lihat tests/CreatesApplication.php),
    // jadi tidak menyentuh database produksi sama sekali.
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function buatUser(string $role, string $username): User
    {
        return User::create([
            'name' => 'Uji '.$role,
            'username' => $username,
            'email' => $username.'@bbm.test',
            'password' => bcrypt('rahasia123'),
            'role' => $role,
        ]);
    }

    public function test_superadmin_bisa_buka_dan_simpan_profil_perusahaan(): void
    {
        $sebelum = CompanyProfile::all();

        try {
            $this->simpanDanPeriksa();
        } finally {
            // kembalikan profil asli, jangan hardcode ke default
            CompanyProfile::put($sebelum);
        }
    }

    private function simpanDanPeriksa(): void
    {
        $admin = $this->buatUser('superadmin', 'uji_superadmin');
        $this->actingAs($admin);

        $this->get('/admin/perusahaan')->assertOk();

        $response = $this->post('/admin/perusahaan', [
            'nama' => 'PT. UJI COBA',
            'subtitle' => 'Sistem Absensi',
            'alamat' => 'Jl. Contoh No. 9',
            'telepon' => '021-999',
            'email' => 'info@uji.test',
            'website' => 'www.uji.test',
            'nama_ttd' => 'Siti Aminah',
            'jabatan_ttd' => 'HRD',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response->assertRedirect(route('company-profile.index'));

        CompanyProfile::flush();
        $profil = CompanyProfile::all();

        $this->assertSame('PT. UJI COBA', $profil['nama']);
        $this->assertSame('Jl. Contoh No. 9', $profil['alamat']);
        $this->assertNotSame('', $profil['logo']);
        Storage::disk('public')->assertExists($profil['logo']);

        $html = $this->get('/admin/perusahaan')->assertOk()->getContent();
        $this->assertStringContainsString('PT. UJI COBA', $html);
        $this->assertStringContainsString('/storage/'.$profil['logo'], $html);

        // hapus logo
        $this->delete('/admin/perusahaan/logo')->assertRedirect(route('company-profile.index'));
        CompanyProfile::flush();
        $this->assertSame('', CompanyProfile::all()['logo']);
    }

    public function test_halaman_perusahaan_hanya_untuk_superadmin(): void
    {
        $admin = $this->buatUser('admin', 'uji_admin');

        $this->actingAs($admin)->get('/admin/perusahaan')->assertForbidden();
    }
}
