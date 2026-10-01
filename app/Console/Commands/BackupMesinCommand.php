<?php

namespace App\Console\Commands;

use App\Models\MachineStatus;
use App\Services\BackupService;
use App\Services\SolutionSoapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Pulihkan data mesin absensi (user + template sidik jari) dari sebuah backup.
 *
 * Dipisah dari backup:kelola karena menulis ke perangkat keras perlu langkah
 * eksplisit --lihat dulu isi machine.json, baru eksekusi.
 */
class BackupMesinCommand extends Command
{
    protected $signature = 'backup:mesin
                            {--backup= : Id backup (default: backup terbaru)}
                            {--pulihkan : benar-benar menulis ke mesin (tanpa ini hanya pratinjau)}
                            {--mesin= : Id mesin tujuan (default: mesin Solution pertama)}';

    protected $description = 'Pratinjau atau pulihkan data mesin absensi dari backup';

    public function handle(BackupService $backup): int
    {
        $daftar = $backup->daftar();

        if ($daftar === []) {
            $this->warn('Belum ada backup. Jalankan: php artisan backup:buat');

            return self::FAILURE;
        }

        $id = $this->option('backup') ?: $daftar[0]['id'];
        $dir = $backup->find($id);
        $file = $dir.'/machine.json';

        if (! is_file($file)) {
            $this->error("Backup '{$id}' tidak punya machine.json (kemungkinan dibuat dengan --tanpa-mesin).");

            return self::FAILURE;
        }

        $snap = json_decode(file_get_contents($file), true);
        $machines = $snap['machines'] ?? [];

        if ($machines === []) {
            $this->error('Backup tidak memuat data mesin.');

            return self::FAILURE;
        }

        $this->info("Isi machine.json pada backup '{$id}':");
        $this->line('');

        foreach ($machines as $m) {
            $this->line("  Mesin #{$m['id']} {$m['nama']} ({$m['ip']}:{$m['port']}) commkey={$m['commkey']}");
            $this->line('    '.($m['terhubung'] ? 'terhubung' : 'TIDAK terhubung saat backup'));

            foreach ($m['users'] as $u) {
                $jari = [];
                foreach ($m['templates'] as $t) {
                    if ($t['user_id'] === $u['user_id']) {
                        $jari[] = $t['finger_id'];
                    }
                }

                $nama = trim((string) $u['nama']);
                $catatanNama = '';

                if ($nama === '') {
                    $lokal = $this->namaLokal((int) $u['user_id']);
                    $catatanNama = $lokal !== ''
                        ? "  <= akan diambil dari DB lokal: '{$lokal}'"
                        : '  <= TIDAK ADA nama di backup maupun DB lokal';
                }

                $this->line(sprintf(
                    '    user %-4s %-14s sidik jari: %s%s',
                    $u['user_id'],
                    $nama !== '' ? $nama : '(nama kosong)',
                    $jari === [] ? '-' : implode(',', $jari),
                    $catatanNama
                ));
            }
            $this->line('');
        }

        if (! $this->option('pulihkan')) {
            $this->comment('Ini pratinjau. Tambahkan --pulihkan untuk benar-benar menulis ke mesin.');
            $this->comment('User "(nama kosong)" akan diambil namanya dari tabel karyawans di database lokal.');

            return self::SUCCESS;
        }

        $target = MachineStatus::whereIn('machine_type', ['solution', 'x100c'])
            ->when($this->option('mesin'), fn ($q, $v) => $q->where('id', (int) $v))
            ->orderByDesc('is_default')
            ->first();

        if (! $target) {
            $this->error('Mesin tujuan tidak ditemukan.');

            return self::FAILURE;
        }

        $this->warn("Akan MENULIS ke mesin: {$target->machine_name} ({$target->machine_ip})");
        $this->line('Sumber data: '.$machines[0]['nama'].' pada backup '.$id);
        $this->line('');

        if (! $this->confirm('Lanjut menulis ke mesin?', false)) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        $layanan = new SolutionSoapService;
        $layanan->setConnection($target->machine_ip, (int) ($target->port ?: 80), (string) $target->username);

        $hasil = $backup->pulihkanMesin(
            $id,
            $layanan,
            log: fn ($m) => $this->line("  {$m}")
        );

        $target->updateStatus($layanan->wasLastConnectionSuccessful());

        $this->line('');
        $this->info("Selesai: {$hasil['user']} user, {$hasil['template']} template, {$hasil['gagal']} gagal.");

        if ($hasil['gagal'] > 0) {
            $this->error('Ada kegagalan. Periksa output di atas.');
        }

        return self::SUCCESS;
    }

    /**
     * Nama karyawan dari database lokal, dipakai untuk pratinjau.
     */
    protected function namaLokal(int $userId): string
    {
        $nama = DB::table('karyawans')
            ->where('id_karyawan', $userId)
            ->value('nama');

        return is_string($nama) ? trim($nama) : '';
    }
}
