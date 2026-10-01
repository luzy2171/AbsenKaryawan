<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupBuatCommand extends Command
{
    protected $signature = 'backup:buat
                            {--tanpa-mesin : Lewati pembacaan data mesin absensi}
                            {--lokasi= : Simpan ke folder lain (default storage/app/backups)}';

    protected $description = 'Buat backup database, file upload, dan data mesin absensi';

    public function handle(BackupService $backup): int
    {
        $this->info('Membuat backup...');
        $this->line('');

        try {
            $dir = $backup->create(
                includeMachine: ! $this->option('tanpa-mesin'),
                log: fn ($m) => $this->line("  {$m}")
            );
        } catch (\Throwable $e) {
            $this->error('Backup gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $manifest = json_decode(file_get_contents($dir.'/manifest.json'), true);

        $this->line('');
        $this->info('Backup selesai.');
        $this->table(
            ['Bagian', 'Keterangan'],
            [
                ['Lokasi', $dir],
                ['Ukuran', $this->ukuran($manifest['file_ukuran'] ?? 0)],
                ['Database', is_file($dir.'/database.sql') ? 'ok' : 'GAGAL'],
                ['File upload', is_file($dir.'/files-public.tar.gz') ? 'ok' : 'GAGAL'],
                ['Mesin', ($manifest['machine']['diambil'] ?? false)
                    ? ($manifest['machine']['template'] ?? 0).' template'
                    : 'dilewati'],
            ]
        );

        $this->line('');
        $this->comment('Catatan: salin folder ini ke tempat lain juga (mis. PC/layanan lain)');
        $this->comment('sebabun file di storage bisa ikut hilang kalau server bermasalah.');

        return self::SUCCESS;
    }

    protected function ukuran(int $byte): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $satuan) {
            if ($byte < 1024) {
                return round($byte, 1).' '.$satuan;
            }
            $byte /= 1024;
        }

        return round($byte, 1).' TB';
    }
}
