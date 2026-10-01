<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

/**
 * Helper gambar untuk template PDF (dompdf).
 *
 * dompdf berjalan dengan enable_remote = false, jadi URL http:// dari asset()
 * tidak bisa dimuat dan logo/tanda tangan justru hilang di PDF. Solusinya:
 * ubah file lokal menjadi data URI (base64) yang langsung disisipkan ke HTML.
 */
class PdfImage
{
    /**
     * Data URI dari file di disk public.
     *
     * @param  string|null  $path  Path relatif di disk public, mis. 'tanda-tangan/abc.png'
     * @return string Data URI, atau string kosong bila file tidak ada / bukan gambar.
     */
    public static function fromPublic(?string $path): string
    {
        if ($path === null || trim($path) === '') {
            return '';
        }

        if (! Storage::disk('public')->exists($path)) {
            return '';
        }

        $mime = self::mimeFromPath($path);

        if ($mime === null) {
            return '';
        }

        $isi = Storage::disk('public')->get($path);

        if ($isi === null || $isi === '') {
            return '';
        }

        return 'data:'.$mime.';base64,'.base64_encode($isi);
    }

    /**
     * Absolute path file di disk public, atau string kosong bila tidak ada.
     */
    public static function localPath(?string $path): string
    {
        if ($path === null || trim($path) === '' || ! Storage::disk('public')->exists($path)) {
            return '';
        }

        return Storage::disk('public')->path($path);
    }

    /**
     * MIME type berdasarkan ekstensi, hanya untuk format gambar.
     */
    protected static function mimeFromPath(string $path): ?string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => null,
        };
    }
}
