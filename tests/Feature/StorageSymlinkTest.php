<?php

namespace Tests\Feature;

use Tests\TestCase;

class StorageSymlinkTest extends TestCase
{
    /**
     * public/storage harus resolve ke storage/app/public milik repo ini.
     *
     * Kalau symlink-nya absolut ke /var/www/html, nginx (yang jalan di host,
     * root /var/www/absen-karyawan/public) akan 404 untuk asset('storage/...')
     * walau file-nya ada.
     */
    public function test_symlink_storage_public_mengunjuk_ke_storage_app_public(): void
    {
        $link = public_path('storage');

        $this->assertTrue(
            is_link($link),
            'public/storage harus berupa symlink, jalankan php artisan storage:link'
        );

        $target = readlink($link);

        $this->assertFalse(
            str_starts_with($target, '/'),
            "public/storage harus symlink relatif, bukan absolut (sekarang: {$target})"
        );

        $this->assertDirectoryExists(
            $link,
            'symlink public/storage dangling; jalankan php artisan storage:link'
        );

        $this->assertSame(
            realpath(storage_path('app/public')),
            realpath($link),
            'public/storage harus menunjuk ke storage/app/public repo ini'
        );
    }
}
