<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Services\AbsensiService;
use App\Http\Controllers\AbsensiController;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Menjalankan penarikan data log mesin otomatis setiap hari jam 23:55 malam
        $schedule->call(function () {
            $absensiService = app(AbsensiService::class);
            $controller = app(AbsensiController::class);

            // Memanggil fungsi penarikan data yang sudah mendukung filter 3 bulan & split masuk/pulang
            $controller->tarikDataDariMesin($absensiService);
        })->dailyAt('19:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
