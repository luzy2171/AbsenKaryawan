<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AbsensiService;

class PengaturanController extends Controller
{
    /**
     * Mengakses Halaman Dashboard Menu Pengaturan Alat
     */
    public function index(AbsensiService $absensiService, Request $request)
    {
        $users = [];
        $logs = [];
        $templates = [];

        // Fitur 1: Aksi tampilkan list User dari Mesin
        if ($request->has('view_users')) {
            $users = $absensiService->getAllUsers();
        }

        // Fitur 2: Aksi tampilkan Log Mentah Gabungan Nama dari Mesin
        if ($request->has('view_logs')) {
            $logs = $absensiService->downloadLogDenganNama();
        }

        // Fitur 3: Aksi download Template Sidik Jari
        if ($request->has('download_fp')) {
            $templates = $absensiService->getFingerprintTemplate(
                $request->input('user_id', '1'),
                $request->input('finger_id', '0')
            );
        }

        return view('pengaturan.index', compact('users', 'logs', 'templates'));
    }

    /**
     * Fitur 4: Proses Kosongkan Log Transaksi Mesin Absensi
     */
    public function clearMachineLogs(AbsensiService $absensiService)
    {
        $result = $absensiService->clearLogData();

        if ($result === "Koneksi Gagal") {
            return back()->with('error', 'Gagal terhubung dengan mesin absensi.');
        }

        return back()->with('status', 'Log transaksi mesin berhasil dibersihkan! Respon Alat: ' . $result);
    }

    /**
     * Fitur 5: Proses Hapus User Langsung dari Menu Pengaturan
     */
    public function hapusUserDariMesin(Request $request, AbsensiService $absensiService)
    {
        $request->validate([
            'user_id' => 'required'
        ]);

        $result = $absensiService->hapusUser($request->input('user_id'));

        if ($result === "Koneksi Gagal") {
            return back()->with('error', 'Gagal terhubung dengan mesin absensi.');
        }

        return back()->with('status', 'Proses Hapus User Berhasil! Respon Alat: ' . $result);
    }

    /**
     * Fitur 6: Memproses Sinkronisasi Waktu Server ke Perangkat Absensi Fisik
     */
    public function synchronizeDeviceTime(AbsensiService $absensiService)
    {
        $result = $absensiService->syncTime();

        if ($result === "Koneksi Gagal") {
            return back()->with('error', 'Gagal menyamakan waktu. Koneksi ke mesin terputus.');
        }

        return back()->with('status', 'Waktu mesin berhasil disinkronkan dengan server web! Respon: ' . $result);
    }

    /**
     * Fitur 7: Memproses Perintah Restart Mesin Absensi Fisik
     */
    public function restartMachine(AbsensiService $absensiService)
    {
        $result = $absensiService->restartDevice();

        if ($result === "Koneksi Gagal") {
            return back()->with('error', 'Gagal merestart perangkat. Koneksi ke mesin terputus.');
        }

        return back()->with('status', 'Perintah restart berhasil dikirim! Mesin absensi sedang memuat ulang. Respon: ' . $result);
    }

    /**
     * Fitur 8: Memproses Upload Template Sidik Jari secara Manual via Pengaturan
     */
    public function uploadSidikJariManual(Request $request, AbsensiService $absensiService)
    {
        $request->validate([
            'user_id' => 'required',
            'finger_id' => 'required',
            'template' => 'required'
        ]);

        $result = $absensiService->uploadSidikJari(
            $request->input('user_id'),
            $request->input('finger_id'),
            $request->input('template')
        );

        if ($result === "Koneksi Gagal") return back()->with('error', 'Gagal terhubung ke mesin.');
        return back()->with('status', 'Template sidik jari berhasil diunggah ke perangkat! Respon: ' . $result);
    }

    /**
     * Fitur 9: Memproses Hapus Template Sidik Jari secara Manual via Pengaturan
     */
    public function hapusSidikJariManual(Request $request, AbsensiService $absensiService)
    {
        $request->validate([
            'user_id' => 'required',
            'finger_id' => 'required'
        ]);

        $result = $absensiService->deleteSidikJari(
            $request->input('user_id'),
            $request->input('finger_id')
        );

        if ($result === "Koneksi Gagal") return back()->with('error', 'Gagal terhubung ke mesin.');
        return back()->with('status', 'Template sidik jari berhasil dihapus dari perangkat! Respon: ' . $result);
    }
}
