<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Sumber tunggal untuk identitas perusahaan (nama PT, logo, dan data kop).
 *
 * Semua halaman (sidebar, login, template cetak PDF, export Excel) membaca
 * nilai dari sini supaya tidak ada nama PT yang hardcode lagi.
 */
class CompanyProfile
{
    public const DEFAULT_NAMA = 'Absensi-BBM';

    public const DEFAULT_SUBTITLE = 'Attendance System';

    public const LOGO_DIR = 'perusahaan';

    /**
     * Key settings yang menyimpan profil perusahaan.
     */
    public const KEYS = [
        'nama',
        'subtitle',
        'logo',
        'alamat',
        'telepon',
        'email',
        'website',
        'nama_ttd',
        'jabatan_ttd',
    ];

    /**
     * Cache nilai profil dalam satu request.
     *
     * @var array<string, string>|null
     */
    protected static ?array $cache = null;

    /**
     * Ambil seluruh nilai profil perusahaan.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        try {
            $tersimpan = DB::table('settings')
                ->whereIn('key', static::KEYS)
                ->pluck('value', 'key')
                ->toArray();
        } catch (\Throwable $e) {
            // Sebelum tabel settings siap (mis. saat install) tetap bisa render halaman.
            $tersimpan = [];
        }

        $profil = [
            'nama' => trim($tersimpan['nama'] ?? '') ?: static::DEFAULT_NAMA,
            'subtitle' => trim($tersimpan['subtitle'] ?? '') ?: static::DEFAULT_SUBTITLE,
            'alamat' => trim($tersimpan['alamat'] ?? ''),
            'telepon' => trim($tersimpan['telepon'] ?? ''),
            'email' => trim($tersimpan['email'] ?? ''),
            'website' => trim($tersimpan['website'] ?? ''),
            'nama_ttd' => trim($tersimpan['nama_ttd'] ?? ''),
            'jabatan_ttd' => trim($tersimpan['jabatan_ttd'] ?? ''),
        ];

        $logo = trim($tersimpan['logo'] ?? '');
        $profil['logo'] = ($logo !== '' && Storage::disk('public')->exists($logo)) ? $logo : '';

        return static::$cache = $profil;
    }

    /**
     * Ambil satu nilai profil, mis. static::get('nama').
     */
    public static function get(string $key, string $default = ''): string
    {
        $profil = static::all();

        $nilai = $profil[$key] ?? '';

        return $nilai !== '' ? $nilai : $default;
    }

    /**
     * URL logo perusahaan, atau string kosong bila belum ada logo.
     */
    public static function logoUrl(): string
    {
        $logo = static::get('logo');

        return $logo !== '' ? asset('storage/'.$logo) : '';
    }

    /**
     * Logo sebagai data URI base64, untuk template PDF.
     *
     * dompdf dibatasi enable_remote = false sehingga logo dari asset()
     * (http://...) tidak termuat dan kop surat jadi kosong.
     */
    public static function logoDataUri(): string
    {
        return PdfImage::fromPublic(static::get('logo'));
    }

    /**
     * URL favicon: pakai logo perusahaan, fallback ke favicon bawaan.
     *
     * Dipakai untuk logo di tab browser, di sebelah alamat "www...".
     */
    public static function faviconUrl(): string
    {
        $logo = static::logoUrl();

        if ($logo === '') {
            return asset('favicon.svg');
        }

        return $logo;
    }

    /**
     * MIME type logo untuk atribut type="..." pada favicon.
     */
    public static function faviconType(): string
    {
        $ext = strtolower(pathinfo(static::get('logo'), PATHINFO_EXTENSION));

        return match ($ext) {
            'svg' => 'image/svg+xml',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/png',
        };
    }

    /**
     * Baris kontak untuk kop (alamat, telepon, email, website) yang tidak kosong.
     *
     * @return array<int, string>
     */
    public static function kontakBaris(): array
    {
        $profil = static::all();
        $baris = [];

        if ($profil['alamat'] !== '') {
            $baris[] = $profil['alamat'];
        }

        $rincian = array_filter([
            $profil['telepon'] !== '' ? 'Telp. '.$profil['telepon'] : '',
            $profil['email'],
            $profil['website'],
        ]);

        if ($rincian !== []) {
            $baris[] = implode(' | ', $rincian);
        }

        return $baris;
    }

    /**
     * Nama perusahaan untuk kop PDF.
     */
    public static function nama(): string
    {
        return static::get('nama', static::DEFAULT_NAMA);
    }

    /**
     * Simpan nilai profil ke tabel settings.
     *
     * @param  array<string, string|null>  $data
     */
    public static function put(array $data): void
    {
        foreach ($data as $key => $value) {
            if (! in_array($key, static::KEYS, true)) {
                continue;
            }

            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => (string) $value, 'updated_at' => now()]
            );
        }

        static::flush();
    }

    /**
     * Bersihkan cache profil (dipanggil setelah penyimpanan / hapus logo).
     */
    public static function flush(): void
    {
        static::$cache = null;
    }
}
