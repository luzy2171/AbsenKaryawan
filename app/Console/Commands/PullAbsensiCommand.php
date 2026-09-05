<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AbsensiService;
use Illuminate\Support\Facades\Storage;
use App\Models\Attendance;
use Carbon\Carbon;

class PullAbsensiCommand extends Command
{
    protected $signature = 'absensi:pull';
    protected $description = 'Tarik data absensi dari mesin dan hapus data absen lama (lebih dari 4 bulan)';

    public function handle(AbsensiService $absensiService)
    {
        $autoPullStatus = Storage::exists('auto_pull_status.txt') ? Storage::get('auto_pull_status.txt') : 'OFF';

        if ($autoPullStatus === 'ON') {
            $this->info('Memulai penarikan data absensi...');
            
            // Proses penarikan data yang sama dengan controller
            $rawLogs = $absensiService->downloadLogTigaBulan();
            $machineStatus = \App\Models\MachineStatus::first();
            
            if (empty($rawLogs)) {
                if ($machineStatus) $machineStatus->updateStatus(false);
                $this->error('Gagal mengambil data atau tidak ada data baru.');
            } else {
                if ($machineStatus) $machineStatus->updateStatus(true);
                
                $jamMasukSetting = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'jam_masuk')->value('value') ?? '08:00';
                $toleransi       = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'toleransi_terlambat')->value('value') ?? '15';
                $batasWaktuMasuk = Carbon::createFromFormat('H:i', $jamMasukSetting)->addMinutes((int)$toleransi)->format('H:i:s');
                $jamLemburMulai = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'jam_lembur_mulai')->value('value') ?? '17:00';

                $dataMasukBaru = 0;
                $dataPulangDiupdate = 0;

                usort($rawLogs, function($a, $b) {
                    return strcmp(is_array($a) ? $a['datetime'] : $a->datetime, is_array($b) ? $b['datetime'] : $b->datetime);
                });

                foreach ($rawLogs as $log) {
                    $pin = is_array($log) ? $log['pin'] : $log->pin;
                    $karyawan = \App\Models\Karyawan::where('id_karyawan', $pin)->first();

                    if ($karyawan) {
                        $datetime = is_array($log) ? $log['datetime'] : $log->datetime;
                        $verified = is_array($log) ? $log['verified'] : $log->verified;
                        
                        $timestamp = Carbon::parse($datetime);
                        $tanggal = $timestamp->toDateString();
                        $jam = $timestamp->toTimeString();
                        $methodVerifikasi = $verified == '1' ? 'Sidik Jari' : 'Password/Lainnya';

                        $attendanceHariIni = Attendance::where('karyawan_id', $karyawan->id)
                                                       ->where('tanggal', $tanggal)
                                                       ->first();

                        if (!$attendanceHariIni) {
                            $statusKehadiran = ($jam > $batasWaktuMasuk) ? 'Terlambat' : 'Hadir';
                            $attendance = Attendance::create([
                                'karyawan_id' => $karyawan->id,
                                'tanggal'     => $tanggal,
                                'jam_masuk'   => $jam,
                                'jam_pulang'  => null,
                                'status'      => $statusKehadiran,
                                'verifikasi'  => $methodVerifikasi
                            ]);
                            $dataMasukBaru++;
                        } else {
                            if ($jam > $attendanceHariIni->jam_masuk) {
                                if (is_null($attendanceHariIni->jam_pulang) || $jam > $attendanceHariIni->jam_pulang) {
                                    $attendanceHariIni->update(['jam_pulang' => $jam]);
                                    $dataPulangDiupdate++;

                                    if ($jam > $jamLemburMulai) {
                                        $waktuMulaiLembur = Carbon::createFromFormat('H:i', $jamLemburMulai);
                                        $waktuPulang = Carbon::createFromFormat('H:i:s', $jam);
                                        $lamaLembur = $waktuMulaiLembur->diffInMinutes($waktuPulang);

                                        if ($lamaLembur > 0) {
                                            \App\Models\Lembur::updateOrCreate(
                                                ['attendance_id' => $attendanceHariIni->id, 'karyawan_id' => $karyawan->id, 'tanggal' => $tanggal],
                                                ['jam_lembur_mulai' => $jamLemburMulai, 'jam_lembur_selesai' => $jam, 'lama_lembur' => $lamaLembur]
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $this->info("Berhasil menambahkan $dataMasukBaru absen baru dan $dataPulangDiupdate update jam pulang.");
            }
        } else {
            $this->info('Auto Pull dinonaktifkan di pengaturan. Melewati penarikan data.');
        }

        // FITUR BARU: Hapus Data Absensi & Lembur yang lebih tua dari 4 Bulan
        $batasHapus = Carbon::now()->subMonths(4)->toDateString();
        
        $this->info("Membersihkan data absensi dan lembur sebelum tanggal: $batasHapus");
        
        // Hapus Lembur lama (foreign key bisa cascade, tapi hapus manual lebih aman)
        $deletedLembur = \App\Models\Lembur::where('tanggal', '<', $batasHapus)->delete();
        // Hapus Absensi lama
        $deletedAbsen = Attendance::where('tanggal', '<', $batasHapus)->delete();
        
        if ($deletedAbsen > 0 || $deletedLembur > 0) {
            \App\Helpers\AuditLogger::logCustom("System CleanUp", "Menghapus otomatis $deletedAbsen data absensi dan $deletedLembur data lembur berumur lebih dari 4 bulan.");
            $this->info("Berhasil menghapus $deletedAbsen data absensi dan $deletedLembur data lembur.");
        } else {
            $this->info('Tidak ada data lama yang perlu dihapus.');
        }
    }
}
