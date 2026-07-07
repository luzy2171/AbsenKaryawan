<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Attendance; // Tetap menggunakan model Attendance bawaan proyekmu
use Carbon\Carbon;

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

        // Mengirimkan semua data ke view 'dashboard'
        return view('dashboard', compact('totalKaryawan', 'hadirHariIni', 'terlambat', 'tidakHadir', 'absensiTerbaru'));
    }
}
