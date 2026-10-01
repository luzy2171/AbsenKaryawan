<?php

namespace App\Helpers;

/**
 * URL asset dengan cache-busting otomatis.
 *
 * Versi diambil dari mtime file, jadi setiap kali CSS diubah browser otomatis
 * mengambil versi baru tanpa perlu hard refresh.
 */
class Asset
{
    /**
     * @param  string  $path  Path relatif dari folder public, mis. 'css/custom.css'
     */
    public static function url(string $path): string
    {
        $versi = @filemtime(public_path($path));

        return asset($path.($versi ? '?v='.$versi : ''));
    }
}
