<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Attendance;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Mengambil tanggal hari ini (Format: YYYY-MM-DD)
        $hariIni = Carbon::today()->toDateString();

        // 1. Menghitung data untuk 4 Kotak Summary di atas
        $totalKaryawan = Karyawan::where('status', 'Aktif')->count();
        $hadirHariIni  = Attendance::where('tanggal', $hariIni)->where('status', 'Hadir')->count();
        $terlambat     = Attendance::where('tanggal', $hariIni)->where('status', 'Terlambat')->count();

        // Karyawan tidak hadir adalah total dikurangi yang sudah melakukan tap masuk
        $tidakHadir    = $totalKaryawan - ($hadirHariIni + $terlambat);
        $tidakHadir    = $tidakHadir < 0 ? 0 : $tidakHadir; // Mencegah nilai minus jika ada error data

        // 2. Mengambil 5 data absensi terbaru hari ini untuk tabel "Absensi Terbaru"
        $absensiTerbaru = Attendance::with('karyawan')
                            ->where('tanggal', $hariIni)
                            ->orderBy('jam_masuk', 'desc')
                            ->take(5)
                            ->get();

        // Mengirimkan semua data ke view 'dashboard.blade.php' atau 'dashboard'
        return view('dashboard', compact('totalKaryawan', 'hadirHariIni', 'terlambat', 'tidakHadir', 'absensiTerbaru'));
    }
}
