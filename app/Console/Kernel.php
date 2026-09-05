<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use App\Services\AbsensiService;
use App\Events\AttendanceRecorded;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // 1. Eksekusi Command Utama untuk Tarik Data dan Hapus Data Lama
        // Kita menggunakan interval dinamis berdasarkan pengaturan database
        $interval = (int) DB::table('settings')->where('key', 'auto_pull_interval')->value('value') ?? 24;
        $interval = max(1, $interval);
        $cronExp = '0 */' . $interval . ' * * *';

        // Jadwalkan Command buatan kita tadi
        $schedule->command('absensi:pull')->cron($cronExp)->withoutOverlapping();
        
        // Catatan: Jika ingin menjalankan hapus log lama tepat tengah malam saja:
        // $schedule->command('absensi:pull')->dailyAt('23:55')->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}