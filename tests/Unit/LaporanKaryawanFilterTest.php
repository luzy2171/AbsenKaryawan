<?php

namespace Tests\Unit;

use App\Http\Controllers\LaporanController;
use App\Services\LaporanKehadiranService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit test untuk normalisasi parameter pemilihan karyawan.
 * Tidak menyentuh database.
 */
class LaporanKaryawanFilterTest extends TestCase
{
    private function parse($raw): array
    {
        $controller = new LaporanController(new LaporanKehadiranService());
        $method = new ReflectionMethod($controller, 'parseKaryawanIds');
        $method->setAccessible(true);

        return $method->invoke($controller, $raw);
    }

    public function test_karyawan_id_kosong_berarti_semua_karyawan(): void
    {
        $this->assertSame([], $this->parse(null));
        $this->assertSame([], $this->parse(''));
        $this->assertSame([], $this->parse([]));
    }

    public function test_karyawan_id_dari_checkbox_menjadi_array_integer(): void
    {
        $this->assertSame([24, 26, 29], $this->parse(['24', '26', '29']));
    }

    public function test_karyawan_id_legacy_comma_separated_tetap_didukung(): void
    {
        $this->assertSame([24, 26], $this->parse('24,26'));
        $this->assertSame([31], $this->parse('31'));
    }

    public function test_nilai_kosong_dan_duplikat_dibuang(): void
    {
        $this->assertSame([24, 26], $this->parse(['24', '', '26', '24']));
        $this->assertSame([24], $this->parse('24,24,24'));
    }

    public function test_nilai_bukan_angka_diabaikan(): void
    {
        $this->assertSame([24], $this->parse(['24', 'semua', 'abc']));
    }
}
