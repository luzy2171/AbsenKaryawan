<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        // WAJIB: pastikan test memakai sqlite in-memory, bukan MySQL produksi.
        // Container menyuntikkan APP_ENV=local dan DB_*=produksi sebagai env
        // sistem, dan env sistem menang atas phpunit.xml/.env.testing.
        // Memaksa di sini (sebelum bootstrap) adalah satu-satunya cara yang
        // benar-benar reliably menutup risiko menghapus data produksi.
        putenv('APP_ENV=testing');
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['APP_ENV'] = 'testing';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Pengaman kedua: kalau entah kenapa config masih menunjuk MySQL,
        // gagal keras daripada diam-diam menghapus data produksi.
        if (config('database.default') !== 'sqlite') {
            throw new \RuntimeException(
                'TEST TIDAK AMAN: koneksi default masih "'.config('database.default').
                '". Test dibatalkan agar database produksi tidak tersentuh.'
            );
        }

        return $app;
    }
}
