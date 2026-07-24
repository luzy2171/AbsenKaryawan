<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Attendance;
use App\Services\AbsensiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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
     * PERBAIKAN LOGIKA: Memproses Penarikan Data Log Mesin Berdasarkan Pengaturan Jam Kerja Dinamis (ANTI-DUPLIKASI)
     */
    public function tarikDataDariMesin(AbsensiService $absensiService)
    {
        // 1. Ambil data log mentah dari mesin via SOAP (Terfilter 3 bulan terakhir)
        $rawLogs = $absensiService->downloadLogTigaBulan();

        if (empty($rawLogs)) {
            return back()->with('error', 'Tidak ada data log absensi baru dalam 3 bulan terakhir atau koneksi mesin terputus.');
        }

        // 2. AMBIL PARAMETER DINAMIS DARI DATABASE SETTINGS (DENGAN FALLBACK DEFAULT)
        $jamMasukSetting = DB::table('settings')->where('key', 'jam_masuk')->value('value') ?? '08:00';
        $toleransi       = DB::table('settings')->where('key', 'toleransi_terlambat')->value('value') ?? '15';
        $batasWaktuMasuk = Carbon::createFromFormat('H:i', $jamMasukSetting)->addMinutes((int)$toleransi)->format('H:i:s');

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

                // 3. CEK KETAT: Apakah karyawan ini SUDAH memiliki catatan absensi PADA TANGGAL TERSEBUT
                $attendanceHariIni = Attendance::where('karyawan_id', $karyawan->id)
                                               ->where('tanggal', $tanggal)
                                               ->first();

                if (!$attendanceHariIni) {
                    // JIKA BELUM ADA RECORD DI TANGGAL ITU: Masuk sebagai scan pertama (Jam Masuk)
                    $statusKehadiran = ($jam > $batasWaktuMasuk) ? 'Terlambat' : 'Hadir';

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
                    // JIKA SUDAH ADA RECORD DI TANGGAL ITU: Update kolom jam_pulang yang sudah ada, JANGAN buat baris baru!
                    if ($jam > $attendanceHariIni->jam_masuk) {
                        // Pastikan tidak menimpa data jam_pulang lama jika jam scan baru bernilai lebih kecil/sama
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

        return back()->with('status', "Sinkronisasi berhasil! Berhasil menambahkan $dataMasukBaru data masuk baru dan memperbarui $dataPulangDiupdate jam pulang.");
    }

    /**
     * UPDATE FITUR CETAK: Pengurutan Rapi Per Karyawan (ID 1 tgl 1-30, ID 2 tgl 1-30, dst.)
     */
    public function cetakLaporan(Request $request)
    {
        $request->validate([
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'karyawan_id'     => 'nullable'
        ]);

        $mulai      = $request->tanggal_mulai;
        $selesai    = $request->tanggal_selesai;
        $karyawanId = $request->karyawan_id;

        // Membangun query dasar log absensi dalam rentang tanggal
        $query = Attendance::with('karyawan')->whereBetween('tanggal', [$mulai, $selesai]);

        // Saring query jika admin memilih karyawan tertentu dari dropdown select
        if ($karyawanId && $karyawanId !== 'semua') {
            $query->where('karyawan_id', $karyawanId);
        }

        $attendancesRaw = $query->get();

        // PROSES PEMADATAN DATA: Gabungkan jam masuk & pulang secara otomatis jika karyawan & tanggal sama
        $cleanedAttendances = [];

        foreach ($attendancesRaw as $att) {
            $key = $att->karyawan_id . '_' . $att->tanggal;

            $jamScan        = $att->jam_masuk ? date('H:i', strtotime($att->jam_masuk)) : null;
            $jamPulangExist = $att->jam_pulang ? date('H:i', strtotime($att->jam_pulang)) : null;

            if (!isset($cleanedAttendances[$key])) {
                $masuk  = $jamScan;
                $pulang = $jamPulangExist;

                if ($jamScan && !$pulang && $jamScan >= '12:00') {
                    $pulang = $jamScan;
                    $masuk  = null;
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

        // KUNCI PENGURUTAN:
        // Urutkan berdasarkan ID/PIN Karyawan secara numerik terlebih dahulu, lalu urutkan Tanggal secara kronologis
        usort($cleanedAttendances, function($a, $b) {
            $pinA = (int) $a['id_karyawan'];
            $pinB = (int) $b['id_karyawan'];

            if ($pinA === $pinB) {
                // Jika karyawan sama, urutkan berdasarkan tanggal (01/07 sampai 30/07)
                return strcmp($a['tanggal'], $b['tanggal']);
            }

            // Urutkan dari PIN terkecil ke terbesar (ID 1, ID 2, ID 3, dst.)
            return $pinA <=> $pinB;
        });

        return view('absensi.cetak', compact('cleanedAttendances', 'mulai', 'selesai'));
    }

    /**
     * FITUR EXPORT EXCEL RAPI (.XLS)
     * Terbagi otomatis dalam kolom rapi dengan Kop Perusahaan dan Pengurutan PIN/ID Karyawan
     */
    public function exportExcel(Request $request)
    {
        $request->validate([
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'karyawan_id'     => 'nullable'
        ]);

        $mulai      = $request->tanggal_mulai;
        $selesai    = $request->tanggal_selesai;
        $karyawanId = $request->karyawan_id;

        $query = Attendance::with('karyawan')->whereBetween('tanggal', [$mulai, $selesai]);

        if ($karyawanId && $karyawanId !== 'semua') {
            $query->where('karyawan_id', $karyawanId);
        }

        $attendancesRaw = $query->get();
        $cleanedAttendances = [];

        foreach ($attendancesRaw as $att) {
            $key = $att->karyawan_id . '_' . $att->tanggal;

            $jamScan        = $att->jam_masuk ? date('H:i', strtotime($att->jam_masuk)) : null;
            $jamPulangExist = $att->jam_pulang ? date('H:i', strtotime($att->jam_pulang)) : null;

            if (!isset($cleanedAttendances[$key])) {
                $masuk  = $jamScan;
                $pulang = $jamPulangExist;

                if ($jamScan && !$pulang && $jamScan >= '12:00') {
                    $pulang = $jamScan;
                    $masuk  = null;
                }

                $cleanedAttendances[$key] = [
                    'id_karyawan' => $att->karyawan->id_karyawan ?? '-',
                    'nama'        => $att->karyawan->nama ?? '-',
                    'tanggal'     => $att->tanggal,
                    'jam_masuk'   => $masuk ? $masuk . ' WIB' : '-',
                    'jam_pulang'  => $pulang ? $pulang . ' WIB' : '-',
                    'status'      => $att->status
                ];
            } else {
                if ($jamScan) {
                    if ($jamScan >= '12:00') {
                        if (!$cleanedAttendances[$key]['jam_pulang'] || $jamScan > $cleanedAttendances[$key]['jam_pulang']) {
                            $cleanedAttendances[$key]['jam_pulang'] = $jamScan . ' WIB';
                        }
                    } else {
                        if (!$cleanedAttendances[$key]['jam_masuk'] || $jamScan < $cleanedAttendances[$key]['jam_masuk']) {
                            $cleanedAttendances[$key]['jam_masuk'] = $jamScan . ' WIB';
                        }
                    }
                }

                if ($jamPulangExist) {
                    if (!$cleanedAttendances[$key]['jam_pulang'] || $jamPulangExist > $cleanedAttendances[$key]['jam_pulang']) {
                        $cleanedAttendances[$key]['jam_pulang'] = $jamPulangExist . ' WIB';
                    }
                }
            }
        }

        // PENGURUTAN RAPI: PIN Terkecil ke Terbesar -> Tanggal Kronologis
        usort($cleanedAttendances, function($a, $b) {
            $pinA = (int) $a['id_karyawan'];
            $pinB = (int) $b['id_karyawan'];

            if ($pinA === $pinB) {
                return strcmp($a['tanggal'], $b['tanggal']);
            }

            return $pinA <=> $pinB;
        });

        $filename = "Laporan_Absensi_" . date('d-m-Y', strtotime($mulai)) . "_s.d_" . date('d-m-Y', strtotime($selesai)) . ".xls";

        // Generate Tampilan HTML Excel Rapi (Sesuai Layout Web / PDF)
        $html = '
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <style>
                body { font-family: Calibri, sans-serif; }
                .title { font-size: 16pt; font-weight: bold; color: #1e7e34; }
                .subtitle { font-size: 10pt; color: #555555; }
                .header-table { font-weight: bold; background-color: #f2f2f2; text-align: center; border: 0.5pt solid #cccccc; }
                .cell-center { text-align: center; border: 0.5pt solid #cccccc; }
                .cell-left { text-align: left; border: 0.5pt solid #cccccc; }
                .status-hadir { font-weight: bold; color: #155724; background-color: #d4edda; text-align: center; border: 0.5pt solid #cccccc; }
                .status-terlambat { font-weight: bold; color: #856404; background-color: #fff3cd; text-align: center; border: 0.5pt solid #cccccc; }
                .status-alpha { font-weight: bold; color: #721c24; background-color: #f8d7da; text-align: center; border: 0.5pt solid #cccccc; }
            </style>
        </head>
        <body>
            <table>
                <tr>
                    <td colspan="7" class="title">PT.BEJO BERKAH MAKMUR</td>
                </tr>
                <tr>
                    <td colspan="7" class="subtitle">LAPORAN DETAIL ABSENSI KARYAWAN | Periode: ' . date('d/m/Y', strtotime($mulai)) . ' s.d ' . date('d/m/Y', strtotime($selesai)) . '</td>
                </tr>
                <tr><td colspan="7"></td></tr>
                <tr class="header-table">
                    <th width="50">No</th>
                    <th width="100">PIN / ID</th>
                    <th width="200">Nama Karyawan</th>
                    <th width="120">Tanggal</th>
                    <th width="120">Jam Masuk</th>
                    <th width="120">Jam Pulang</th>
                    <th width="100">Status</th>
                </tr>';

        $no = 1;
        foreach ($cleanedAttendances as $row) {
            $statusClass = 'status-hadir';
            if ($row['status'] == 'Terlambat') {
                $statusClass = 'status-terlambat';
            } elseif ($row['status'] == 'Alpha') {
                $statusClass = 'status-alpha';
            }

            $html .= '
            <tr>
                <td class="cell-center">' . $no++ . '</td>
                <td class="cell-center" style="color: #d9534f; font-weight: bold;">' . $row['id_karyawan'] . '</td>
                <td class="cell-left" style="font-weight: bold;">' . $row['nama'] . '</td>
                <td class="cell-center">' . date('d/m/Y', strtotime($row['tanggal'])) . '</td>
                <td class="cell-center">' . $row['jam_masuk'] . '</td>
                <td class="cell-center">' . $row['jam_pulang'] . '</td>
                <td class="' . $statusClass . '">' . $row['status'] . '</td>
            </tr>';
        }

        $html .= '
            </table>
        </body>
        </html>';

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0'
        ]);
    }
}
