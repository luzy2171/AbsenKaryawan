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
        $interval = (int) DB::table('settings')->where('key', 'auto_pull_interval')->value('value') ?? 24;
        $interval = max(1, $interval);
        $cronExp = '0 */' . $interval . ' * * *';

        $schedule->call(function () {
            $absensiService = app(AbsensiService::class);

            $startTime = microtime(true);
            $rawLogs = $absensiService->downloadLogTigaBulan();

            // Update status mesin berdasarkan hasil koneksi
            $machineStatus = \App\Models\MachineStatus::first();
            
            if (empty($rawLogs)) {
                // Update status mesin menjadi offline
                if ($machineStatus) {
                    $machineStatus->updateStatus(false);
                }
                info('Tidak ada data log absensi baru dalam 3 bulan terakhir atau koneksi mesin terputus.');
                return;
            }

            // Hitung response time dan update status mesin menjadi online
            $responseTime = round((microtime(true) - $startTime) * 1000);
            if ($machineStatus) {
                $machineStatus->updateStatus(true, $responseTime);
            }

            $jamMasukSetting = DB::table('settings')->where('key', 'jam_masuk')->value('value') ?? '08:00';
            $toleransi       = DB::table('settings')->where('key', 'toleransi_terlambat')->value('value') ?? '15';
            $batasWaktuMasuk = \Carbon\Carbon::createFromFormat('H:i', $jamMasukSetting)->addMinutes((int)$toleransi)->format('H:i:s');

            $dataMasukBaru = 0;
            $dataPulangDiupdate = 0;

            usort($rawLogs, function($a, $b) {
                return strcmp($a['datetime'], $b['datetime']);
            });

            foreach ($rawLogs as $log) {
                $karyawan = \App\Models\Karyawan::where('id_karyawan', $log['pin'])->first();

                if ($karyawan) {
                    $timestamp = \Carbon\Carbon::parse($log['datetime']);
                    $tanggal = $timestamp->toDateString();
                    $jam = $timestamp->toTimeString();
                    $methodVerifikasi = $log['verified'] == '1' ? 'Sidik Jari' : 'Password/Lainnya';

                    $attendanceHariIni = \App\Models\Attendance::where('karyawan_id', $karyawan->id)
                        ->where('tanggal', $tanggal)
                        ->first();

                    if (!$attendanceHariIni) {
                        $statusKehadiran = ($jam > $batasWaktuMasuk) ? 'Terlambat' : 'Hadir';

                        $attendance = \App\Models\Attendance::create([
                            'karyawan_id' => $karyawan->id,
                            'tanggal'     => $tanggal,
                            'jam_masuk'   => $jam,
                            'jam_pulang'  => null,
                            'status'      => $statusKehadiran,
                            'verifikasi'  => $methodVerifikasi
                        ]);

                        // Broadcast event untuk real-time update
                        event(new AttendanceRecorded($attendance));

                        $dataMasukBaru++;
                    } else {
                        if ($jam > $attendanceHariIni->jam_masuk) {
                            if (is_null($attendanceHariIni->jam_pulang) || $jam > $attendanceHariIni->jam_pulang) {
                                $attendanceHariIni->update([
                                    'jam_pulang' => $jam
                                ]);
                                $dataPulangDiupdate++;
                            }
                        }
                    }
                }
            }

            info("Sinkronisasi berhasil! Berhasil menambahkan $dataMasukBaru data masuk baru dan memperbarui $dataPulangDiupdate jam pulang.");
        })->cron($cronExp);
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}