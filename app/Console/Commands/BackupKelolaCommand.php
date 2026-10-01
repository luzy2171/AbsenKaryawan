<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupKelolaCommand extends Command
{
    protected $signature = 'backup:kelola
                            {--hapus= : Hapus backup dengan id tersebut}
                            {--pulasikan= : Pulihkan backup dengan id tersebut (lihat opsi --yang)}
                            {--yang=database : Bagian yang dipulihkan: database, file, mesin, atau semua}';

    protected $description = 'Lihat, hapus, atau pulihkan backup yang ada';

    public function handle(BackupService $backup): int
    {
        if ($id = $this->option('hapus')) {
            $backup->hapus($id);
            $this->info("Backup '{$id}' dihapus.");

            return self::SUCCESS;
        }

        $daftar = $backup->daftar();

        if ($daftar === []) {
            $this->warn('Belum ada backup. Jalankan: php artisan backup:buat');

            return self::SUCCESS;
        }

        $this->table(
            ['ID (stempel)', 'Dibuat', 'DB', 'File', 'Mesin', 'Ukuran'],
            array_map(fn ($b) => [
                $b['id'],
                $b['dibuat'] ?? '-',
                $b['ada_db'] ? 'v' : '-',
                $b['ada_file'] ? 'v' : '-',
                $b['ada_mesin'] ? 'v' : '-',
                $this->ukuran($b['ukuran']),
            ], $daftar)
        );

        if (! $id = $this->option('pulasikan')) {
            return self::SUCCESS;
        }

        return $this->pulihkan($backup, $id, (string) $this->option('yang'));
    }

    protected function pulihkan(BackupService $backup, string $id, string $yang): int
    {
        $dir = $backup->find($id);

        $this->warn("Memulihkan backup '{$id}' dari {$dir}");
        $this->line('');

        $target = [];

        if ($yang === 'semua' || $yang === 'database') {
            $target[] = 'database';
        }
        if ($yang === 'semua' || $yang === 'file') {
            $target[] = 'file';
        }
        if ($yang === 'semua' || $yang === 'mesin') {
            $target[] = 'mesin';
        }

        if ($target === []) {
            $this->error("Bagian '{$yang}' tidak dikenal.");

            return self::FAILURE;
        }

        // Pulihkan database = data saat ini ditimpa
        if (in_array('database', $target, true)
            && ! $this->confirm('Lanjut? Data database SEKARANG akan ditimpa', false)) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        if (in_array('file', $target, true)
            && ! $this->confirm('Lanjut? Folder storage/app/public SEKARANG akan diganti isinya', false)) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        if (in_array('mesin', $target, true)
            && ! $this->confirm('Lanjut? Data mesin absensi akan DITULIS (user + sidik jari)', false)) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        $log = fn ($m) => $this->line("  {$m}");

        try {
            if (in_array('database', $target, true)) {
                $backup->pulihkanDatabase($id, log: $log);
            }

            if (in_array('file', $target, true)) {
                $backup->pulihkanFile($id, $log);
            }

            if (in_array('mesin', $target, true)) {
                $this->line('');
                $this->comment('Memulihkan mesin. Daftar user ada di '.$dir.'/machine.json');
                $this->comment('Pastikan nama user di backup sudah benar sebelum menulis.');
                $this->comment('Jalankan: php artisan backup:mesin --pulihkan='.$id);
            }
        } catch (\Throwable $e) {
            $this->error('Gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->line('');
        $this->info('Selesai.');

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
