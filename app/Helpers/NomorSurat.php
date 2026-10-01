<?php

namespace App\Helpers;

use App\Models\Leave;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Nomor surat untuk surat persetujuan/penolakan izin-sakit-cuti.
 *
 * Format global diatur sekali di halaman Tanda Tangan, contoh:
 *   {nomor}/{jenis}/{tahun}  ->  0001/CUTI/2026
 *
 * Nomor urut disimpan di tabel settings per jenis dan per tahun, jadi setiap
 * surat selalu unik dan tidak perlu diinput manual.
 */
class NomorSurat
{
    /**
     * Token yang boleh dipakai di format.
     */
    public const PLACEHOLDERS = [
        '{nomor}' => 'Nomor urut, mis. 0001',
        '{jenis}' => 'Jenis pengajuan: CUTI / SAKIT / IZIN',
        '{bulan}' => 'Bulan dua digit, mis. 10',
        '{tahun}' => 'Tahun empat digit, mis. 2026',
    ];

    public const DEFAULT_FORMAT = '{nomor}/{jenis}/{bulan}/{tahun}';

    /**
     * Key settings untuk format dan nomor urut.
     */
    public const KEY_FORMAT = 'nomor_surat_format';

    public const JENIS = ['Cuti', 'Sakit', 'Izin'];

    /**
     * Format yang sedang dipakai.
     */
    public static function format(): string
    {
        $format = trim((string) self::getSetting(self::KEY_FORMAT, ''));

        if ($format === '') {
            return self::DEFAULT_FORMAT;
        }

        return $format;
    }

    /**
     * Nomor berikutnya untuk satu jenis pengajuan.
     */
    public static function berikutnya(string $jenis, int $tahun): int
    {
        return (int) self::getSetting(self::keyUrut($jenis, $tahun), '0') + 1;
    }

    /**
     * Ambil nomor urut baru sekaligus menambahnya 1.
     *
     * Dipakai supaya dua surat yang terbit bersamaan tidak mendapat nomor sama.
     */
    public static function ambilNomor(string $jenis, int $tahun): int
    {
        $key = self::keyUrut($jenis, $tahun);

        // Baris dikunci supaya dua proses tidak mendapat angka yang sama.
        return DB::transaction(function () use ($key) {
            $nilai = (int) self::getSetting($key, '0');

            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => (string) ($nilai + 1),
                    'description' => 'Nomor urut surat terakhir: '.ucfirst(strtolower(self::jenisDariKey($key))),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            return $nilai + 1;
        });
    }

    /**
     * Susun nomor surat final dari format dan nomor urut.
     */
    public static function susun(string $jenis, int $urut, int $tahun): string
    {
        return str_replace(
            ['{nomor}', '{jenis}', '{bulan}', '{tahun}'],
            [
                str_pad((string) $urut, 4, '0', STR_PAD_LEFT),
                strtoupper($jenis),
                date('m', $tahun === 0 ? time() : mktime(0, 0, 0, (int) date('n'), 1, $tahun)),
                (string) $tahun,
            ],
            self::format()
        );
    }

    /**
     * Nomor surat lengkap untuk pengajuan, tanpa menambah counter.
     *
     * Dipakai saat melihat pratinjau atau surat yang sudah terbit.
     */
    public static function untuk(Leave $leave): string
    {
        if (trim((string) ($leave->nomor_surat ?? '')) !== '') {
            return trim($leave->nomor_surat);
        }

        $tahun = (int) Carbon::parse($leave->created_at)->format('Y');

        return self::susun($leave->jenis, (int) $leave->id, $tahun);
    }

    /**
     * Pasifkan nomor surat ke pengajuan memakai counter global.
     */
    public static function pasifkan(Leave $leave): void
    {
        if (trim((string) ($leave->nomor_surat ?? '')) !== '') {
            return;
        }

        $tahun = (int) Carbon::parse($leave->created_at)->format('Y');
        $urut = self::ambilNomor($leave->jenis, $tahun);

        $leave->forceFill(['nomor_surat' => self::susun($leave->jenis, $urut, $tahun)])->saveQuietly();
    }

    public static function keyUrut(string $jenis, int $tahun): string
    {
        return 'nomor_surat_urutan_'.strtolower($jenis).'_'.$tahun;
    }

    protected static function jenisDariKey(string $key): string
    {
        if (preg_match('/urutan_([a-z]+)_/', $key, $m)) {
            return $m[1];
        }

        return '';
    }

    protected static function getSetting(string $key, string $default): string
    {
        try {
            $nilai = DB::table('settings')->where('key', $key)->value('value');
        } catch (\Throwable $e) {
            return $default;
        }

        return $nilai === null ? $default : (string) $nilai;
    }
}
