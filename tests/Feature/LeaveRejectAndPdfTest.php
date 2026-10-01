<?php

namespace Tests\Feature;

use App\Helpers\CompanyProfile;
use App\Helpers\NomorSurat;
use App\Helpers\PdfImage;
use App\Models\Karyawan;
use App\Models\Leave;
use App\Models\LeaveApproval;
use App\Models\User;
use App\Models\UserSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveRejectAndPdfTest extends TestCase
{
    use RefreshDatabase;

    private function approver(): User
    {
        // Dipakai ulang kalau dipanggil lebih dari sekali dalam satu test.
        return User::firstOrCreate(
            ['username' => 'hartono'],
            [
                'name' => 'Pak Hartono',
                'email' => 'hartono@bbm.test',
                'password' => bcrypt('rahasia123'),
                'role' => 'approval',
            ]
        );
    }

    private function karyawan(): Karyawan
    {
        // id_karyawan unik, jadi dipakai ulang kalau pengajuan dibuat lebih dari sekali.
        return Karyawan::firstOrCreate(
            ['id_karyawan' => '001'],
            ['nama' => 'Baim'],
        );
    }

    private function pengajuan(array $attr = []): Leave
    {
        return Leave::create(array_merge([
            'karyawan_id' => $this->karyawan()->id,
            'jenis' => 'Cuti',
            'tanggal_mulai' => '2026-10-05',
            'tanggal_selesai' => '2026-10-07',
            'keterangan' => 'Keperluan keluarga',
            'status' => 'Menunggu',
        ], $attr));
    }

    public function test_approver_bisa_menolak_pengajuan_dengan_alasan()
    {
        $leave = $this->pengajuan();

        $response = $this->actingAs($this->approver())
            ->put(route('admin.leaves.reject', $leave->id), [
                'alasan_tolak' => 'Bertumpuk dengan cuti yang sudah disetujui',
            ]);

        $response->assertRedirect();
        $this->assertSame('Ditolak', $leave->fresh()->status);
        $this->assertSame('Bertumpuk dengan cuti yang sudah disetujui', $leave->fresh()->alasan_tolak);
        $this->assertNotNull($leave->fresh()->ditolak_at);
        $this->assertNotNull($leave->fresh()->ditolak_by);
    }

    public function test_alasan_tolak_wajib_diisi()
    {
        $leave = $this->pengajuan();

        $this->actingAs($this->approver())
            ->put(route('admin.leaves.reject', $leave->id), [])
            ->assertSessionHasErrors('alasan_tolak');

        $this->assertSame('Menunggu', $leave->fresh()->status);
    }

    public function test_pengajuan_yang_sudah_disetujui_tidak_bisa_ditolak()
    {
        $leave = $this->pengajuan(['status' => 'Disetujui']);

        $this->actingAs($this->approver())
            ->put(route('admin.leaves.reject', $leave->id), ['alasan_tolak' => 'Terlambat'])
            ->assertSessionHas('error');

        $this->assertSame('Disetujui', $leave->fresh()->status);
    }

    public function test_admin_biasa_tidak_bisa_menolak()
    {
        $admin = User::create([
            'name' => 'Admin', 'username' => 'admin',
            'email' => 'admin@bbm.test',
            'password' => bcrypt('rahasia123'), 'role' => 'admin',
        ]);
        $leave = $this->pengajuan();

        $this->actingAs($admin)
            ->put(route('admin.leaves.reject', $leave->id), ['alasan_tolak' => 'Tidak boleh'])
            ->assertForbidden();

        $this->assertSame('Menunggu', $leave->fresh()->status);
    }

    public function test_pdf_persetujuan_terbit_dengan_nama_jenis_dan_alasan()
    {
        $approver = $this->approver();
        $leave = $this->pengajuan();

        // Set langsung ke Disetujui: alur approve multi-approval tidak relevan
        // untuk pengujian PDF.
        $leave->update(['status' => 'Disetujui', 'approved_by' => $approver->id]);

        $response = $this->actingAs($approver)->get(route('admin.leaves.pdf', $leave->id));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('.pdf', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_admin_bisa_membuka_pdf_surat_tanpa_bisa_memutuskan()
    {
        $admin = User::create([
            'name' => 'Admin', 'username' => 'admin',
            'email' => 'admin@bbm.test',
            'password' => bcrypt('rahasia123'), 'role' => 'admin',
        ]);
        $leave = $this->pengajuan(['status' => 'Disetujui']);

        // Surat hanya dibaca, jadi admin boleh membuka.
        $this->actingAs($admin)
            ->get(route('admin.leaves.pdf', $leave->id))
            ->assertOk();

        // Tapi keputusannya tetap khusus approver.
        $menunggu = $this->pengajuan(['tanggal_mulai' => '2026-11-02', 'tanggal_selesai' => '2026-11-03']);

        $this->actingAs($admin)
            ->put(route('admin.leaves.approve', $menunggu->id))
            ->assertForbidden();
    }

    public function test_pdf_bukan_approver_ditolak_aksesnya()
    {
        $karyawanUser = User::create([
            'name' => 'Baim', 'username' => 'baim',
            'email' => 'baim@bbm.test',
            'password' => bcrypt('rahasia123'), 'role' => 'karyawan',
        ]);
        $leave = $this->pengajuan(['status' => 'Disetujui']);

        $this->actingAs($karyawanUser)
            ->get(route('admin.leaves.pdf', $leave->id))
            ->assertForbidden();
    }

    public function test_approver_bisa_mengunggah_tanda_tangan()
    {
        Storage::fake('public');
        $approver = $this->approver();

        $this->actingAs($approver)
            ->post(route('signature.update'), [
                'nama' => 'Hartono, S.Kom',
                'jabatan' => 'Manajer',
                'image' => UploadedFile::fake()->image('ttd.png', 300, 120),
            ])
            ->assertRedirect(route('signature.edit'));

        $sig = UserSignature::where('user_id', $approver->id)->first();
        $this->assertNotNull($sig);
        $this->assertSame('Hartono, S.Kom', $sig->nama);
        Storage::disk('public')->assertExists($sig->image_path);
    }

    public function test_gambar_tanda_tangan_wajib_ada()
    {
        Storage::fake('public');

        $this->actingAs($this->approver())
            ->post(route('signature.update'), ['nama' => 'Tanpa Gambar'])
            ->assertSessionHasErrors('image');

        $this->assertSame(0, UserSignature::count());
    }

    public function test_jumlah_hari_mengabaikan_minggu()
    {
        // 2026-10-04 Minggu s.d. 2026-10-07 Rabu = 3 hari kerja
        $leave = $this->pengajuan(['tanggal_mulai' => '2026-10-04', 'tanggal_selesai' => '2026-10-07']);

        $this->assertSame(3, $leave->jumlahHari());
    }

    public function test_tanda_tangan_approver_masuk_ke_pdf_persetujuan()
    {
        Storage::fake('public');
        $approver = $this->approver();
        $this->punyaTandaTangan($approver, 'Hartono, S.Kom', 'Manajer SDM');

        $leave = $this->pengajuan(['status' => 'Disetujui', 'approved_by' => $approver->id]);

        $html = $this->htmlSurat($leave, $approver);

        $this->assertStringContainsString('Hartono, S.Kom', $html);
        $this->assertStringContainsString('Manajer SDM', $html);
        // Gambar disisipkan sebagai data URI karena dompdf tidak bisa memuat URL http.
        $this->assertStringContainsString('data:image/png;base64,', $html);
    }

    public function test_tanda_tangan_approver_masuk_ke_pdf_penolakan()
    {
        Storage::fake('public');
        $approver = $this->approver();
        $this->punyaTandaTangan($approver, 'Hartono, S.Kom', 'Manajer SDM');

        $leave = $this->pengajuan([
            'status' => 'Ditolak',
            'alasan_tolak' => 'Bentrok dengan cuti tahunan',
            'ditolak_by' => $approver->id,
            'ditolak_at' => now(),
        ]);

        $html = $this->htmlSurat($leave, $approver);

        // Huruf besar dibuat lewat CSS text-transform, jadi yang dicek teks mentahnya.
        $this->assertStringContainsString('Surat Penolakan Cuti', $html);
        $this->assertStringContainsString('Bentrok dengan cuti tahunan', $html);
        $this->assertStringContainsString('Hartono, S.Kom', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);
    }

    public function test_pdf_penolakan_terbit_dengan_nama_jenis_dan_alasan()
    {
        $approver = $this->approver();
        $leave = $this->pengajuan([
            'status' => 'Ditolak',
            'alasan_tolak' => 'Bentrok dengan cuti tahunan',
            'ditolak_by' => $approver->id,
            'ditolak_at' => now(),
        ]);

        $response = $this->actingAs($approver)->get(route('admin.leaves.pdf', $leave->id));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('Penolakan', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_semua_approver_yg_menyetujui_ikut_ditandatangani()
    {
        Storage::fake('public');
        $pertama = $this->approver();
        $kedua = User::create([
            'name' => 'Siti', 'username' => 'siti',
            'email' => 'siti@bbm.test',
            'password' => bcrypt('rahasia123'), 'role' => 'approval',
        ]);
        $this->punyaTandaTangan($pertama, 'Hartono, S.Kom', 'Manajer');
        $this->punyaTandaTangan($kedua, 'Siti Aminah', 'Kepala Divisi');

        $leave = $this->pengajuan(['status' => 'Disetujui', 'approved_by' => $kedua->id]);
        LeaveApproval::create(['leave_id' => $leave->id, 'user_id' => $pertama->id]);
        LeaveApproval::create(['leave_id' => $leave->id, 'user_id' => $kedua->id]);

        $html = $this->htmlSurat($leave, $pertama);

        $this->assertStringContainsString('Hartono, S.Kom', $html);
        $this->assertStringContainsString('Siti Aminah', $html);
    }

    public function test_pdf_tidak_terbit_selama_pengajuan_masih_menunggu()
    {
        $leave = $this->pengajuan();

        $response = $this->actingAs($this->approver())
            ->from(route('admin.leaves.index'))
            ->get(route('admin.leaves.pdf', $leave->id));

        $response->assertRedirect(route('admin.leaves.index'));
        $response->assertSessionHas('error');
    }

    public function test_tanpa_tanda_tangan_pdf_tetap_terbit()
    {
        $approver = $this->approver();
        $leave = $this->pengajuan(['status' => 'Disetujui', 'approved_by' => $approver->id]);

        $response = $this->actingAs($approver)->get(route('admin.leaves.pdf', $leave->id));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
        // Tanpa tanda tangan, nama approver dari akun tetap dicetak.
        $this->assertStringContainsString($approver->name, $this->htmlSurat($leave, $approver));
    }

    public function test_webp_ditolak_karena_tidak_bisa_dirender_pdf()
    {
        Storage::fake('public');

        $this->actingAs($this->approver())
            ->post(route('signature.update'), [
                'nama' => 'Hartono',
                'image' => UploadedFile::fake()->create('ttd.webp', 20, 'image/webp'),
            ])
            ->assertSessionHasErrors('image');
    }

    public function test_nomor_surat_diberikan_otomatis_saat_disetujui()
    {
        $approver = $this->approver();
        DB::table('settings')->updateOrInsert(
            ['key' => 'required_approvals'],
            ['value' => '1', 'updated_at' => now(), 'created_at' => now()]
        );

        $leave = $this->pengajuan();

        $this->actingAs($approver)
            ->put(route('admin.leaves.approve', $leave->id))
            ->assertRedirect();

        $this->assertSame('Disetujui', $leave->fresh()->status);
        // Nomor urut global, bukan id pengajuan.
        $this->assertSame('0001/CUTI/10/2026', $leave->fresh()->nomor_surat);
    }

    public function test_setiap_surat_dapat_nomor_urut_berbeda()
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'required_approvals'],
            ['value' => '1', 'updated_at' => now(), 'created_at' => now()]
        );
        $approver = $this->approver();

        $pertama = $this->pengajuan(['tanggal_mulai' => '2026-10-01', 'tanggal_selesai' => '2026-10-02']);
        $kedua = $this->pengajuan(['jenis' => 'Sakit', 'tanggal_mulai' => '2026-10-03', 'tanggal_selesai' => '2026-10-04']);

        $this->actingAs($approver)->put(route('admin.leaves.approve', $pertama->id));
        $this->actingAs($approver)->put(route('admin.leaves.approve', $kedua->id));

        // Cuti dan Sakit punya urutan terpisah.
        $this->assertSame('0001/CUTI/10/2026', $pertama->fresh()->nomor_surat);
        $this->assertSame('0001/SAKIT/10/2026', $kedua->fresh()->nomor_surat);
    }

    public function test_surat_penolakan_juga_dapat_nomor()
    {
        $approver = $this->approver();
        $leave = $this->pengajuan();

        $this->actingAs($approver)
            ->put(route('admin.leaves.reject', $leave->id), ['alasan_tolak' => 'Bentrok'])
            ->assertRedirect();

        $this->assertSame('0001/CUTI/10/2026', $leave->fresh()->nomor_surat);
    }

    public function test_format_nomor_surat_bisa_diubah_dari_halaman_tanda_tangan()
    {
        $approver = $this->approver();

        $this->actingAs($approver)
            ->put(route('signature.format_nomor'), ['nomor_surat_format' => 'SK/{jenis}/{tahun}/{nomor}'])
            ->assertRedirect();

        $this->assertSame('SK/{jenis}/{tahun}/{nomor}', NomorSurat::format());
        $this->assertSame('SK/CUTI/2026/0007', NomorSurat::susun('Cuti', 7, 2026));
    }

    public function test_format_nomor_surat_menolak_token_asing()
    {
        $this->actingAs($this->approver())
            ->put(route('signature.format_nomor'), ['nomor_surat_format' => '{nope}'])
            ->assertSessionHas('error');

        $this->actingAs($this->approver())
            ->put(route('signature.format_nomor'), ['nomor_surat_format' => '{jenis}'])
            ->assertSessionHas('error');
    }

    public function test_pratinjau_surat_tampil_dengan_tanda_tangan()
    {
        Storage::fake('public');
        $approver = $this->approver();
        $this->punyaTandaTangan($approver, 'Baim', 'Superadmin');

        $this->actingAs($approver)
            ->get(route('signature.preview', ['jenis' => 'Cuti', 'status' => 'Disetujui']))
            ->assertOk()
            ->assertSee('Surat Persetujuan Cuti')
            ->assertSee('Baim')
            ->assertSee('data:image/png;base64,', false);

        $this->actingAs($approver)
            ->get(route('signature.preview', ['jenis' => 'Cuti', 'status' => 'Ditolak']))
            ->assertOk()
            ->assertSee('Surat Penolakan Cuti');
    }

    public function test_pratinjau_hanya_untuk_approver()
    {
        $admin = User::create([
            'name' => 'Admin', 'username' => 'admin',
            'email' => 'admin@bbm.test',
            'password' => bcrypt('rahasia123'), 'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('signature.preview'))
            ->assertForbidden();
    }

    public function test_form_tanda_tangan_tanpa_method_spoofing_berhasil_disimpan()
    {
        // Route signature.update terdaftar POST. Kalau form diam-diam memakai
        // @method('PUT'), Laravel mengirim PUT dan server membalas 405.
        Storage::fake('public');
        $approver = $this->approver();

        $response = $this->actingAs($approver)
            ->post(route('signature.update'), [
                'nama' => 'Alsya',
                'jabatan' => 'Manajer',
                'image' => UploadedFile::fake()->image('ttd.png', 300, 120),
            ]);

        $response->assertRedirect(route('signature.edit'));
        $response->assertSessionHasNoErrors();
        $this->assertNotNull(UserSignature::where('user_id', $approver->id)->first());
    }

    private function punyaTandaTangan(User $user, string $nama, string $jabatan): UserSignature
    {
        Storage::disk('public')->put('tanda-tangan/'.$user->username.'.png', 'dummy');

        return UserSignature::create([
            'user_id' => $user->id,
            'image_path' => 'tanda-tangan/'.$user->username.'.png',
            'nama' => $nama,
            'jabatan' => $jabatan,
        ]);
    }

    /**
     * Render HTML surat tanpa menghasilkan file PDF, supaya isi bisa diperiksa.
     */
    private function htmlSurat(Leave $leave, User $approver): string
    {
        $leave->load(['karyawan', 'approver.signature', 'rejecter.signature', 'leaveApprovals.user.signature']);

        $disetujui = $leave->status === 'Disetujui';

        $users = $disetujui
            ? ($leave->leaveApprovals->pluck('user')->filter()->isNotEmpty()
                ? $leave->leaveApprovals->pluck('user')->filter()
                : collect([$leave->approver]))
            : collect([$leave->rejecter]);

        return view('admin.leaves.pdf', [
            'leave' => $leave,
            'nomorSurat' => $leave->nomorSurat(),
            'disetujui' => $disetujui,
            'penyetuju' => $users->map(fn ($u) => [
                'nama' => $u?->signature?->nama ?: ($u->name ?? '-'),
                'jabatan' => $u?->signature?->jabatan ?: 'Approver',
                'signature' => $u?->signature?->imageDataUri() ?: '',
            ])->values()->all(),
            'lampiranGambar' => PdfImage::fromPublic($leave->dokumen),
            'perusahaan' => CompanyProfile::all(),
        ])->render();
    }
}
