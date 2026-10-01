<?php

namespace Tests\Unit;

use App\Models\Karyawan;
use App\Services\LaporanKehadiranService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit test logika rekap Laporan Kehadiran & Jam Kerja.
 * Hanya menguji metode murni (tanpa akses database).
 */
class LaporanKehadiranServiceTest extends TestCase
{
    private function invoke(string $method, array $args)
    {
        $service = new LaporanKehadiranService;
        $reflection = new ReflectionMethod($service, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($service, $args);
    }

    private function defaults(): array
    {
        return [
            'total_hari_kerja' => 10,
            'jumlah_terlambat' => 0,
            'total_lembur_menit' => 0,
            'izin' => 0,
            'sakit' => 0,
            'cuti' => 0,
            'alpha' => 0,
        ];
    }

    public function test_tanpa_pelanggaran_riport_menampilkan_tepat_waktu(): void
    {
        $hasil = $this->invoke('susunRiport', [$this->defaults()]);

        $this->assertSame('Tepat Waktu (100%)', $hasil['teks']);
        $this->assertSame('success', $hasil['kategori']);
    }

    public function test_terlambat_menampilkan_jumlah_kali(): void
    {
        $hasil = $this->invoke('susunRiport', [array_merge($this->defaults(), ['jumlah_terlambat' => 2])]);

        $this->assertSame('Terlambat 2x', $hasil['teks']);
        $this->assertSame('warning', $hasil['kategori']);
    }

    public function test_izin_sakit_dan_cuti_menampilkan_jumlah_hari(): void
    {
        $hasil = $this->invoke('susunRiport', [array_merge($this->defaults(), [
            'izin' => 1,
            'sakit' => 2,
        ])]);

        $this->assertSame('Izin 1 Hari | Sakit 2 Hari', $hasil['teks']);
        $this->assertSame('warning', $hasil['kategori']);
    }

    public function test_lembur_bulat_ditampilkan_tanapa_desimal(): void
    {
        $hasil = $this->invoke('susunRiport', [array_merge($this->defaults(), ['total_lembur_menit' => 240])]);

        $this->assertSame('Lembur 4 Jam', $hasil['teks']);
    }

    public function test_lembur_pecahan_menampilkan_desimal(): void
    {
        $hasil = $this->invoke('susunRiport', [array_merge($this->defaults(), ['total_lembur_menit' => 713])]);

        $this->assertSame('Lembur 11.9 Jam', $hasil['teks']);
    }

    public function test_alpha_berkategori_bahaya(): void
    {
        $hasil = $this->invoke('susunRiport', [array_merge($this->defaults(), ['alpha' => 2])]);

        $this->assertSame('Alpha 2 Hari', $hasil['teks']);
        $this->assertSame('danger', $hasil['kategori']);
    }

    public function test_tanpa_hari_kerja_ditandai_belum_ada_data(): void
    {
        $hasil = $this->invoke('susunRiport', [array_merge($this->defaults(), ['total_hari_kerja' => 0])]);

        $this->assertSame('Belum Ada Data', $hasil['teks']);
        $this->assertSame('danger', $hasil['kategori']);
    }

    public function test_hari_efektif_mengacu_pada_tanggal_dari_data(): void
    {
        $absensi = new Collection([
            (object) ['tanggal' => '2026-09-01', 'status' => 'Hadir'],
            (object) ['tanggal' => '2026-09-01', 'status' => 'Terlambat'],
            (object) ['tanggal' => '2026-09-02', 'status' => 'Hadir'],
            (object) ['tanggal' => '2026-09-06', 'status' => 'Cuti'],
        ]);

        $jumlah = $this->invoke('hitungJumlahHariEfektif', [
            Carbon::parse('2026-09-01'),
            Carbon::parse('2026-09-30'),
            $absensi,
        ]);

        // 2026-09-06 adalah Minggu sehingga tidak dihitung sebagai hari kerja
        $this->assertSame(2, $jumlah);
    }

    public function test_hari_efektif_fallback_ke_kalender_bila_data_kosong(): void
    {
        $jumlah = $this->invoke('hitungJumlahHariEfektif', [
            Carbon::parse('2026-09-01'),
            Carbon::parse('2026-09-07'),
            new Collection,
        ]);

        // 1-7 September 2026: Senin-Sabtu = 6 hari kerja
        $this->assertSame(6, $jumlah);
    }

    public function test_jabatan_dan_divisi_default_bila_kosong(): void
    {
        $karyawan = new Karyawan(['jabatan' => '-', 'departemen' => '-']);

        $hasil = $this->invoke('jabatanDivisi', [$karyawan]);

        $this->assertSame('Staf / Umum', $hasil);
    }

    public function test_jabatan_dan_divisi_menampilkan_nilai_asli(): void
    {
        $karyawan = new Karyawan(['jabatan' => 'Network Engineer', 'departemen' => 'IT']);

        $hasil = $this->invoke('jabatanDivisi', [$karyawan]);

        $this->assertSame('Network Engineer / IT', $hasil);
    }

    public function test_nama_hari_menggunakan_bahasa_indonesia(): void
    {
        $this->assertSame('Senin', $this->invoke('namaHari', [Carbon::parse('2026-09-07')]));
        $this->assertSame('Jumat', $this->invoke('namaHari', [Carbon::parse('2026-09-11')]));
        $this->assertSame('Minggu', $this->invoke('namaHari', [Carbon::parse('2026-09-06')]));
    }

    public function test_format_menit_menjadi_jam_dan_menit(): void
    {
        $this->assertSame('45 menit', $this->invoke('formatMenit', [45]));
        $this->assertSame('4 jam', $this->invoke('formatMenit', [240]));
        $this->assertSame('7 jam 23 menit', $this->invoke('formatMenit', [443]));
    }

    public function test_hari_tanpa_catatan_ditandai_alpha(): void
    {
        $standar = [
            'jam_masuk' => '09:00',
            'toleransi_terlambat' => 120,
        ];

        $baris = $this->invoke('susunBarisHarian', [Carbon::parse('2026-09-07'), null, $standar]);

        $this->assertSame('Alpha', $baris['status']);
        $this->assertSame('danger', $baris['kategori']);
        $this->assertSame(0.0, $baris['durasi_jam']);
        $this->assertStringContainsString('Tanpa absensi', $baris['keterangan']);
    }

    public function test_hadir_dengan_scan_pulang_lengkap(): void
    {
        $standar = [
            'jam_masuk' => '09:00',
            'toleransi_terlambat' => 120,
        ];

        $att = (object) [
            'status' => 'Hadir',
            'jam_masuk' => '09:00:00',
            'jam_pulang' => '17:30:00',
            'lembur' => null,
        ];

        $baris = $this->invoke('susunBarisHarian', [Carbon::parse('2026-09-01'), $att, $standar]);

        $this->assertSame('Hadir', $baris['status']);
        $this->assertSame('success', $baris['kategori']);
        $this->assertSame(8.5, $baris['durasi_jam']);
        $this->assertSame(0, $baris['menit_terlambat']);
    }

    public function test_terlambat_menghitung_menit_di_luar_toleransi(): void
    {
        $standar = [
            'jam_masuk' => '09:00',
            'toleransi_terlambat' => 120,
        ];

        // Batas toleransi 11:00, scan 11:34 -> terlambat 34 menit
        $att = (object) [
            'status' => 'Terlambat',
            'jam_masuk' => '11:34:00',
            'jam_pulang' => '18:00:00',
            'lembur' => null,
        ];

        $baris = $this->invoke('susunBarisHarian', [Carbon::parse('2026-09-09'), $att, $standar]);

        $this->assertSame('Terlambat', $baris['status']);
        $this->assertSame('warning', $baris['kategori']);
        $this->assertSame(34, $baris['menit_terlambat']);
        $this->assertStringContainsString('Terlambat 34 menit', $baris['keterangan']);
    }

    public function test_tidak_ada_scan_pulang_ditandai_khusus(): void
    {
        $standar = [
            'jam_masuk' => '09:00',
            'toleransi_terlambat' => 120,
        ];

        $att = (object) [
            'status' => 'Hadir',
            'jam_masuk' => '09:14:00',
            'jam_pulang' => null,
            'lembur' => null,
        ];

        $baris = $this->invoke('susunBarisHarian', [Carbon::parse('2026-09-04'), $att, $standar]);

        $this->assertSame(0.0, $baris['durasi_jam']);
        $this->assertStringContainsString('belum ada scan pulang', $baris['keterangan']);
    }

    public function test_lembur_harian_ditambahkan_ke_keterangan(): void
    {
        $standar = [
            'jam_masuk' => '09:00',
            'toleransi_terlambat' => 120,
        ];

        $att = (object) [
            'status' => 'Hadir',
            'jam_masuk' => '09:00:00',
            'jam_pulang' => '19:00:00',
            'lembur' => (object) ['lama_lembur' => 60],
        ];

        $baris = $this->invoke('susunBarisHarian', [Carbon::parse('2026-09-11'), $att, $standar]);

        $this->assertSame(1.0, $baris['lembur_jam']);
        $this->assertSame('warning', $baris['kategori']);
        $this->assertStringContainsString('lembur 1 jam', $baris['keterangan']);
    }

    public function test_izin_dan_sakit_tidak_menambah_jam_kerja(): void
    {
        $standar = [
            'jam_masuk' => '09:00',
            'toleransi_terlambat' => 120,
        ];

        foreach (['Izin' => 'Izin (tidak menambah jam kerja)', 'Sakit' => 'Sakit (tidak menambah jam kerja)'] as $status => $harapan) {
            $att = (object) [
                'status' => $status,
                'jam_masuk' => null,
                'jam_pulang' => null,
                'lembur' => null,
            ];

            $baris = $this->invoke('susunBarisHarian', [Carbon::parse('2026-09-08'), $att, $standar]);

            $this->assertSame($status, $baris['status']);
            $this->assertSame('warning', $baris['kategori']);
            $this->assertSame(0.0, $baris['durasi_jam']);
            $this->assertSame($harapan, $baris['keterangan']);
        }
    }
}
