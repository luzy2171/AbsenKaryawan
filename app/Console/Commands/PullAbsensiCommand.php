<?php

namespace App\Console\Commands;

use App\Events\AttendanceRecorded;
use App\Helpers\AuditLogger;
use App\Http\Controllers\AbsensiController;
use App\Models\Attendance;
use App\Models\Karyawan;
use App\Models\Lembur;
use App\Models\MachineStatus;
use App\Services\SolutionSoapService;
use App\Services\SolutionX100CService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PullAbsensiCommand extends Command
{
    protected $signature = 'absensi:pull';

    protected $description = 'Tarik data absensi dari mesin dan hapus data absen lama (lebih dari 4 bulan)';

    public function handle(SolutionX100CService $zktecoService, SolutionSoapService $soapService)
    {
        // Kunci yang sama dengan tombol manual di browser, supaya scheduler dan
        // klik pengguna tidak pernah menarik data bersamaan. TTL 3 jam, jauh
        // lebih lama dari lama penarikan.
        $lock = Cache::lock(
            AbsensiController::LOCK_ABSENSI,
            AbsensiController::LOCK_TTL_ABSENSI
        );

        if (! $lock->get()) {
            $this->warn('Penarikan data absensi sudah berjalan di proses lain, dilewati.');

            return Command::SUCCESS;
        }

        try {
            $this->tarik($zktecoService, $soapService);
        } finally {
            $lock->release();
        }
    }

    protected function tarik(SolutionX100CService $zktecoService, SolutionSoapService $soapService)
    {
        $autoPullStatus = Storage::exists('auto_pull_status.txt') ? Storage::get('auto_pull_status.txt') : 'OFF';

        if ($autoPullStatus === 'ON') {
            $this->info('Memulai penarikan data absensi...');

            $rawLogs = [];
            $machines = MachineStatus::whereIn('machine_type', ['solution', 'x100c'])->get();

            foreach ($machines as $m) {
                $service = $this->serviceForMachine($m, $zktecoService, $soapService);
                $logs = $service->downloadLogTigaBulan();
                $rawLogs = array_merge($rawLogs, (array) $logs);
                $m->updateStatus($service->wasLastConnectionSuccessful());
            }

            if (empty($rawLogs)) {
                $this->error('Gagal mengambil data atau tidak ada data baru dari semua mesin.');
            } else {
                $jamMasukSetting = DB::table('settings')->where('key', 'jam_masuk')->value('value') ?? '08:00';
                $toleransi = DB::table('settings')->where('key', 'toleransi_terlambat')->value('value') ?? '15';
                $batasWaktuMasuk = Carbon::createFromFormat('H:i', $jamMasukSetting)->addMinutes((int) $toleransi)->format('H:i:s');
                $jamLemburMulai = DB::table('settings')->where('key', 'jam_lembur_mulai')->value('value') ?? '17:00';

                $dataMasukBaru = 0;
                $dataPulangDiupdate = 0;

                usort($rawLogs, function ($a, $b) {
                    return strcmp(is_array($a) ? $a['datetime'] : $a->datetime, is_array($b) ? $b['datetime'] : $b->datetime);
                });

                foreach ($rawLogs as $log) {
                    $pin = is_array($log) ? $log['pin'] : $log->pin;
                    $karyawan = Karyawan::where('id_karyawan', $pin)->first();

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

                        if (! $attendanceHariIni) {
                            $statusKehadiran = ($jam > $batasWaktuMasuk) ? 'Terlambat' : 'Hadir';
                            // firstOrCreate, bukan create: kalau proses lain
                            // sambil itu membuat baris untuk tanggal yang sama,
                            // unique index (karyawan_id, tanggal) menolak dan
                            // kita pakai baris yang sudah ada.
                            $attendance = Attendance::firstOrCreate(
                                ['karyawan_id' => $karyawan->id, 'tanggal' => $tanggal],
                                ['jam_masuk' => $jam, 'jam_pulang' => null, 'status' => $statusKehadiran, 'verifikasi' => $methodVerifikasi]
                            );
                            if ($attendance->wasRecentlyCreated) {
                                event(new AttendanceRecorded($attendance));
                                $dataMasukBaru++;
                            }
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
                                            Lembur::updateOrCreate(
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
                AuditLogger::absensiPulled($dataMasukBaru + $dataPulangDiupdate);
                $this->info("Berhasil menambahkan $dataMasukBaru absen baru dan $dataPulangDiupdate update jam pulang.");
            }
        } else {
            $this->info('Auto Pull dinonaktifkan di pengaturan. Melewati penarikan data.');
        }

        // FITUR BARU: Hapus Data Absensi & Lembur yang lebih tua dari 4 Bulan
        $batasHapus = Carbon::now()->subMonths(4)->toDateString();

        $this->info("Membersihkan data absensi dan lembur sebelum tanggal: $batasHapus");

        // Hapus Lembur lama (foreign key bisa cascade, tapi hapus manual lebih aman)
        $deletedLembur = Lembur::where('tanggal', '<', $batasHapus)->delete();
        // Hapus Absensi lama
        $deletedAbsen = Attendance::where('tanggal', '<', $batasHapus)->delete();

        if ($deletedAbsen > 0 || $deletedLembur > 0) {
            AuditLogger::logCustom('System CleanUp', "Menghapus otomatis $deletedAbsen data absensi dan $deletedLembur data lembur berumur lebih dari 4 bulan.");
            $this->info("Berhasil menghapus $deletedAbsen data absensi dan $deletedLembur data lembur.");
        } else {
            $this->info('Tidak ada data lama yang perlu dihapus.');
        }
    }

    protected function serviceForMachine($machine, SolutionX100CService $binaryService, SolutionSoapService $soapService)
    {
        $transport = strtolower((string) env('SOLUTION_TRANSPORT', 'soap'));
        if ($transport !== 'binary' && ($transport === 'soap' || (int) ($machine->port ?? 0) === 80)) {
            $soapService->setConnection(
                $machine->machine_ip,
                (int) env('SOLUTION_SOAP_PORT', 80),
                $machine->username
            );

            return $soapService;
        }

        $binaryService->setConnection($machine->machine_ip, $machine->port ?? 4370);

        return $binaryService;
    }
}
