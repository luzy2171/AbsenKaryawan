<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Attendance;
use App\Models\MachineStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Mengambil tanggal hari ini (Format: YYYY-MM-DD)
        $hariIni = Carbon::today()->toDateString();

        // 1. Menghitung data untuk 4 Kotak Summary di atas
        $totalKaryawan = Karyawan::where('status', 'Aktif')->count();

        // PENTING: Menghitung keterlambatan hari ini
        $terlambat     = Attendance::where('tanggal', $hariIni)->where('status', 'Terlambat')->count();

        // PERBAIKAN: "Hadir Hari Ini" sekarang menghitung yang 'Hadir' (Tepat Waktu) DAN yang 'Terlambat'
        // Agar counter kotak hijau di dashboard tidak bernilai 0 saat semua orang terlambat
        $hadirHariIni  = Attendance::where('tanggal', $hariIni)
                            ->whereIn('status', ['Hadir', 'Terlambat'])
                            ->count();

        // Karyawan tidak hadir (Alpha) adalah total karyawan dikurangi yang sudah melakukan tap masuk hari ini
        $tidakHadir    = $totalKaryawan - $hadirHariIni;
        $tidakHadir    = $tidakHadir < 0 ? 0 : $tidakHadir; // Mencegah nilai minus jika ada error data

        // 2. Mengambil 5 data absensi terbaru hari ini untuk tabel "Absensi Terbaru"
        $absensiTerbaru = Attendance::with('karyawan')
                            ->where('tanggal', $hariIni)
                            ->orderBy('jam_masuk', 'desc')
                            ->take(5)
                            ->get();

        // 3. Data untuk Grafik Tren Kehadiran Mingguan (7 hari terakhir)
        $trendMingguan = $this->getTrendMingguan();

        // 4. Data untuk Grafik Tren Kehadiran Bulanan (30 hari terakhir)
        $trendBulanan = $this->getTrendBulanan();

        // 5. Status Mesin Absensi
        $machineStatus = MachineStatus::first();

        // Mengirimkan semua data ke view 'dashboard'
        return view('dashboard', compact(
            'totalKaryawan', 
            'hadirHariIni', 
            'terlambat', 
            'tidakHadir', 
            'absensiTerbaru',
            'trendMingguan',
            'trendBulanan',
            'machineStatus'
        ));
    }

    /**
     * Mengambil data tren kehadiran 7 hari terakhir
     */
    private function getTrendMingguan()
    {
        $labels = [];
        $hadir = [];
        $terlambat = [];
        $alpha = [];

        for ($i = 6; $i >= 0; $i--) {
            $tanggal = Carbon::today()->subDays($i);
            $labels[] = $tanggal->format('D'); // Mon, Tue, Wed, etc
            
            $totalKaryawan = Karyawan::where('status', 'Aktif')->count();
            
            $hadirCount = Attendance::where('tanggal', $tanggal->toDateString())
                ->where('status', 'Hadir')
                ->count();
            
            $terlambatCount = Attendance::where('tanggal', $tanggal->toDateString())
                ->where('status', 'Terlambat')
                ->count();
            
            $alphaCount = $totalKaryawan - ($hadirCount + $terlambatCount);
            $alphaCount = $alphaCount < 0 ? 0 : $alphaCount;
            
            $hadir[] = $hadirCount;
            $terlambat[] = $terlambatCount;
            $alpha[] = $alphaCount;
        }

        return [
            'labels' => $labels,
            'hadir' => $hadir,
            'terlambat' => $terlambat,
            'alpha' => $alpha,
        ];
    }

    /**
     * Mengambil data tren kehadiran 30 hari terakhir
     */
    private function getTrendBulanan()
    {
        $labels = [];
        $hadir = [];
        $terlambat = [];

        for ($i = 29; $i >= 0; $i--) {
            $tanggal = Carbon::today()->subDays($i);
            $labels[] = $tanggal->format('d/m'); // 01/09, 02/09, etc
            
            $hadirCount = Attendance::where('tanggal', $tanggal->toDateString())
                ->where('status', 'Hadir')
                ->count();
            
            $terlambatCount = Attendance::where('tanggal', $tanggal->toDateString())
                ->where('status', 'Terlambat')
                ->count();
            
            $hadir[] = $hadirCount;
            $terlambat[] = $terlambatCount;
        }

        return [
            'labels' => $labels,
            'hadir' => $hadir,
            'terlambat' => $terlambat,
        ];
    }

    /**
     * API endpoint untuk mendapatkan stats dashboard
     * Digunakan untuk real-time updates
     */
    public function getStats()
    {
        $hariIni = Carbon::today()->toDateString();
        $totalKaryawan = Karyawan::where('status', 'Aktif')->count();
        $terlambat = Attendance::where('tanggal', $hariIni)->where('status', 'Terlambat')->count();
        $hadirHariIni = Attendance::where('tanggal', $hariIni)
                            ->whereIn('status', ['Hadir', 'Terlambat'])
                            ->count();
        $tidakHadir = $totalKaryawan - $hadirHariIni;
        $tidakHadir = $tidakHadir < 0 ? 0 : $tidakHadir;

        return response()->json([
            'totalKaryawan' => $totalKaryawan,
            'hadirHariIni' => $hadirHariIni,
            'terlambat' => $terlambat,
            'tidakHadir' => $tidakHadir,
        ]);
    }
}
