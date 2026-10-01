<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackupBersihkanCommand extends Command
{
    protected $signature = 'backup:bersihkan
                            {--simpan=14 : Berapa backup terakhir yang dipertahankan}
                            {--hari=30 : Hapus backup yang lebih tua dari N hari}';

    protected $description = 'Hapus backup lama sesuai batas jumlah dan umur';

    public function handle(BackupService $backup): int
    {
        $simpan = max(1, (int) $this->option('simpan'));
        $hariMaks = max(1, (int) $this->option('hari'));

        $daftar = $backup->daftar();

        if ($daftar === []) {
            $this->info('Tidak ada backup untuk dibersihkan.');

            return self::SUCCESS;
        }

        $batasUmur = now()->subDays($hariMaks);
        $hapus = [];
        $pertahankan = [];

        foreach ($daftar as $i => $b) {
            if ($i < $simpan) {
                $pertahankan[] = $b['id'];

                continue;
            }

            $dibuat = $b['dibuat'] ? Carbon::parse($b['dibuat']) : null;
            if ($dibuat === null || $dibuat->lt($batasUmur)) {
                $hapus[] = $b['id'];
            } else {
                $pertahankan[] = $b['id'];
            }
        }

        foreach ($hapus as $id) {
            $backup->hapus($id);
            $this->line("  dihapus: {$id}");
        }

        $this->info(sprintf(
            'Selesai: %d dihapus, %d dipertahankan (maks %d / %d hari).',
            count($hapus),
            count($pertahankan),
            $simpan,
            $hariMaks
        ));

        return self::SUCCESS;
    }
}
