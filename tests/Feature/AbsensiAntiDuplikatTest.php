<?php

namespace Tests\Feature;

use App\Http\Controllers\AbsensiController;
use App\Models\Attendance;
use App\Models\Karyawan;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AbsensiAntiDuplikatTest extends TestCase
{
    use RefreshDatabase;

    private function karyawan(): Karyawan
    {
        return Karyawan::firstOrCreate(
            ['id_karyawan' => '001'],
            ['nama' => 'Baim'],
        );
    }

    public function test_unique_index_menolak_baris_kembar()
    {
        $k = $this->karyawan();

        Attendance::create([
            'karyawan_id' => $k->id, 'tanggal' => '2026-10-01',
            'jam_masuk' => '08:00', 'status' => 'Hadir',
        ]);

        $this->expectException(QueryException::class);

        Attendance::create([
            'karyawan_id' => $k->id, 'tanggal' => '2026-10-01',
            'jam_masuk' => '08:00', 'status' => 'Hadir',
        ]);
    }

    public function test_first_or_create_tidak_menggandakan_data()
    {
        $k = $this->karyawan();
        $atribut = ['jam_masuk' => '08:00', 'jam_pulang' => null, 'status' => 'Hadir', 'verifikasi' => 'Sidik Jari'];

        $pertama = Attendance::firstOrCreate(['karyawan_id' => $k->id, 'tanggal' => '2026-10-01'], $atribut);
        $kedua = Attendance::firstOrCreate(['karyawan_id' => $k->id, 'tanggal' => '2026-10-01'], $atribut);

        $this->assertTrue($pertama->wasRecentlyCreated);
        $this->assertFalse($kedua->wasRecentlyCreated);
        $this->assertSame($pertama->id, $kedua->id);
        $this->assertSame(1, Attendance::where('karyawan_id', $k->id)->where('tanggal', '2026-10-01')->count());
    }

    public function test_karyawan_berbeda_tetap_boleh_satu_baris_per_hari()
    {
        $a = Karyawan::create(['id_karyawan' => '002', 'nama' => 'Linda']);
        $b = Karyawan::create(['id_karyawan' => '003', 'nama' => 'Sita']);

        foreach ([$a, $b] as $k) {
            Attendance::create([
                'karyawan_id' => $k->id, 'tanggal' => '2026-10-01',
                'jam_masuk' => '08:00', 'status' => 'Hadir',
            ]);
        }

        $this->assertSame(2, Attendance::where('tanggal', '2026-10-01')->count());
    }

    public function test_kunci_absensi_memakai_nama_yang_sama_dengan_controller()
    {
        // Controller dan command harus memakai satu kunci, kalau tidak maka
        // scheduler dan tombol manual tetap bisa jalan bersamaan.
        $this->assertSame('sinkronisasi-absensi', AbsensiController::LOCK_ABSENSI);
        $this->assertGreaterThanOrEqual(120, AbsensiController::LOCK_TTL_ABSENSI);
    }

    public function test_pull_absensi_dilewati_saat_kunci_sedang_dipegang()
    {
        Storage::put('auto_pull_status.txt', 'ON');
        Cache::lock(
            AbsensiController::LOCK_ABSENSI,
            AbsensiController::LOCK_TTL_ABSENSI
        )->get();

        $keluar = $this->artisan('absensi:pull')->run();

        $this->assertSame(0, $keluar);
    }
}
