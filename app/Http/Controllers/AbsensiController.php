<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Attendance;
use App\Services\AbsensiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class AbsensiController extends Controller
{
    /**
     * Halaman Dashboard Absensi
     * Menghitung rangkuman untuk grafik dan kartu di UI
     */
    public function dashboard()
    {
        $hariIni = Carbon::today()->toDateString();

        $totalKaryawan = Karyawan::where('status', 'Aktif')->count();

        // Hitung status kehadiran berdasarkan data hari ini
        $hadirHariIni = Attendance::where('tanggal', $hariIni)->where('status', 'Hadir')->count();
        $terlambat    = Attendance::where('tanggal', $hariIni)->where('status', 'Terlambat')->count();

        // Karyawan yang belum tap jari hari ini dianggap Alpha
        $tidakHadir   = $totalKaryawan - ($hadirHariIni + $terlambat);
        $tidakHadir   = $tidakHadir < 0 ? 0 : $tidakHadir;

        // Ambil 5 riwayat absensi terbaru untuk dipasang di tabel "Absensi Terbaru"
        $absensiTerbaru = Attendance::with('karyawan')
                            ->where('tanggal', $hariIni)
                            ->orderBy('jam_masuk', 'desc')
                            ->take(5)
                            ->get();

        return view('dashboard', compact('totalKaryawan', 'hadirHariIni', 'terlambat', 'tidakHadir', 'absensiTerbaru'));
    }

    /**
     * Halaman Utama Log Absensi (Tabel Catatan Absensi)
     */
    public function index(Request $request)
    {
        // Ambil filter tanggal dari UI (jika kosong, default ke hari ini)
        $tanggalFilter = $request->input('tanggal', Carbon::today()->toDateString());
        $statusFilter  = $request->input('status');
        $search        = $request->input('search');

        $query = Attendance::with('karyawan')->where('tanggal', $tanggalFilter);

        // Jika user memilih filter status tertentu
        if ($statusFilter && $statusFilter !== 'Semua Status') {
            $query->where('status', $statusFilter);
        }

        // Filter pencarian berdasarkan nama karyawan melalui relasi
        if ($search != '') {
            $query->whereHas('karyawan', function($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%');
            });
        }

        $attendances = $query->orderBy('jam_masuk', 'desc')->get();

        // AMBIL STATUS SAKELAR DARI STORAGE (Default OFF)
        $autoPullStatus = Storage::exists('auto_pull_status.txt') ? Storage::get('auto_pull_status.txt') : 'OFF';

        // UPDATE: Ambil semua data karyawan untuk keperluan dropdown filter cetak
        $karyawans = Karyawan::orderBy('nama', 'asc')->get();

        return view('absensi.index', compact('attendances', 'tanggalFilter', 'statusFilter', 'autoPullStatus', 'karyawans'));
    }

    /**
     * FITUR BARU: Mengubah Status Sakelar ON/OFF Tarik Otomatis via Menu Absensi
     */
    public function toggleAutoPull(Request $request)
    {
        $request->validate([
            'status' => 'required|in:ON,OFF'
        ]);

        Storage::put('auto_pull_status.txt', $request->status);

        return back()->with('status', 'Status tarik data otomatis berhasil diubah menjadi: ' . $request->status);
    }

    /**
     * PERBAIKAN LOGIKA: Memproses Penarikan Data Log Mesin Tanpa Duplikasi Baris Baru
     */
    public function tarikDataDariMesin(AbsensiService $absensiService)
    {
        // 1. Ambil data log mentah dari mesin via SOAP (Terfilter 3 bulan terakhir)
        $rawLogs = $absensiService->downloadLogTigaBulan();

        if (empty($rawLogs)) {
            return back()->with('error', 'Tidak ada data log absensi baru dalam 3 bulan terakhir atau koneksi mesin terputus.');
        }

        $dataMasukBaru = 0;
        $dataPulangDiupdate = 0;

        // Urutkan log dari yang paling lama ke paling baru (kronologis) agar masuk dulu baru pulang
        usort($rawLogs, function($a, $b) {
            return strcmp($a['datetime'], $b['datetime']);
        });

        foreach ($rawLogs as $log) {
            // Cocokkan PIN mesin dengan data karyawan di database
            $karyawan = Karyawan::where('id_karyawan', $log['pin'])->first();

            if ($karyawan) {
                $timestamp = Carbon::parse($log['datetime']);
                $tanggal = $timestamp->toDateString();
                $jam = $timestamp->toTimeString();
                $methodVerifikasi = $log['verified'] == '1' ? 'Sidik Jari' : 'Password/Lainnya';

                // 2. KUNCI UTAMA: Cek apakah karyawan ini SUDAH memiliki catatan absensi PADA TANGGAL TERSEBUT
                $attendanceHariIni = Attendance::where('karyawan_id', $karyawan->id)
                                               ->where('tanggal', $tanggal)
                                               ->first();

                if (!$attendanceHariIni) {
                    // JIKA BELUM ADA RECORD DI TANGGAL ITU: Berarti ini adalah Tap Pertama (Jam Masuk)
                    $statusKehadiran = ($jam > '08:00:00') ? 'Terlambat' : 'Hadir';

                    Attendance::create([
                        'karyawan_id' => $karyawan->id,
                        'tanggal'     => $tanggal,
                        'jam_masuk'   => $jam,
                        'jam_pulang'  => null,
                        'status'      => $statusKehadiran,
                        'verifikasi'  => $methodVerifikasi
                    ]);

                    $dataMasukBaru++;
                } else {
                    // JIKA SUDAH ADA RECORD DI TANGGAL ITU: Update kolom jam_pulang yang sudah ada, JANGAN membuat baris baru!
                    if ($jam > $attendanceHariIni->jam_masuk) {
                        $attendanceHariIni->update([
                            'jam_pulang' => $jam
                        ]);

                        $dataPulangDiupdate++;
                    }
                }
            }
        }

        return back()->with('status', "Sinkronisasi berhasil! Berhasil menambahkan $dataMasukBaru data masuk baru dan memperbarui $dataPulangDiupdate jam pulang.");
    }

    /**
     * UPDATE FITUR: Menampilkan Halaman Cetak Laporan Detail Vertikal Bersih + Filter ID/Nama Karyawan Spesifik
     */
    public function cetakLaporan(Request $request)
    {
        $request->validate([
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'karyawan_id'     => 'nullable'
        ]);

        $mulai   = $request->tanggal_mulai;
        $selesai = $request->tanggal_selesai;
        $karyawanId = $request->karyawan_id;

        // Membangun query dasar log absensi dalam rentang tanggal
        $query = Attendance::with('karyawan')->whereBetween('tanggal', [$mulai, $selesai]);

        // UPDATE: Saring query jika admin memilih karyawan tertentu dari dropdown select
        if ($karyawanId && $karyawanId !== 'semua') {
            $query->where('karyawan_id', $karyawanId);
        }

        $attendancesRaw = $query->orderBy('tanggal', 'asc')
                                ->orderBy('jam_masuk', 'asc')
                                ->get();

        // PROSES PEMADATAN DATA: Gabungkan jam masuk & pulang secara otomatis jika karyawan & tanggal sama
        $cleanedAttendances = [];

        foreach ($attendancesRaw as $att) {
            $key = $att->karyawan_id . '_' . $att->tanggal;

            $jamScan = $att->jam_masuk ? date('H:i', strtotime($att->jam_masuk)) : null;
            $jamPulangExist = $att->jam_pulang ? date('H:i', strtotime($att->jam_pulang)) : null;

            if (!isset($cleanedAttendances[$key])) {
                // Jika data awal belum dimasukkan ke penampung cleaned
                $masuk = $jamScan;
                $pulang = $jamPulangExist;

                // Fallback: Jika scan tunggal terdeteksi di sore hari (>= 12:00), geser ke kolom pulang
                if ($jamScan && !$pulang && $jamScan >= '12:00') {
                    $pulang = $jamScan;
                    $masuk = null;
                }

                $cleanedAttendances[$key] = [
                    'id_karyawan' => $att->karyawan->id_karyawan ?? '-',
                    'nama'        => $att->karyawan->nama ?? '-',
                    'tanggal'     => $att->tanggal,
                    'jam_masuk'   => $masuk,
                    'jam_pulang'  => $pulang,
                    'status'      => $att->status
                ];
            } else {
                // Jika baris duplikat ditemukan di database, lebur nilainya ke record yang sudah ada
                if ($jamScan) {
                    if ($jamScan >= '12:00') {
                        if (!$cleanedAttendances[$key]['jam_pulang'] || $jamScan > $cleanedAttendances[$key]['jam_pulang']) {
                            $cleanedAttendances[$key]['jam_pulang'] = $jamScan;
                        }
                    } else {
                        if (!$cleanedAttendances[$key]['jam_masuk'] || $jamScan < $cleanedAttendances[$key]['jam_masuk']) {
                            $cleanedAttendances[$key]['jam_masuk'] = $jamScan;
                        }
                    }
                }

                if ($jamPulangExist) {
                    if (!$cleanedAttendances[$key]['jam_pulang'] || $jamPulangExist > $cleanedAttendances[$key]['jam_pulang']) {
                        $cleanedAttendances[$key]['jam_pulang'] = $jamPulangExist;
                    }
                }
            }
        }

        // Urutkan kembali hasil kompresi secara kronologis berdasarkan tanggal
        usort($cleanedAttendances, function($a, $b) {
            return strcmp($a['tanggal'], $b['tanggal']);
        });

        return view('absensi.cetak', compact('cleanedAttendances', 'mulai', 'selesai'));
    }
}
