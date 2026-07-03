<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\PengaturanController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. Rute untuk Halaman Utama / Otomatis masuk ke Dashboard
Route::get('/', function () {
    return redirect('/dashboard');
});

// 2. Rute untuk Halaman Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// 3. Rute untuk Halaman Absensi (Tabel Catatan Absensi)
Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi.index');

// 4. Rute untuk Proses Tarik Data, Sakelar Otomatis, & Cetak Laporan di Menu Absensi
Route::post('/absensi/tarik', [AbsensiController::class, 'tarikDataDariMesin'])->name('absensi.tarik');
Route::post('/absensi/toggle-auto-pull', [AbsensiController::class, 'toggleAutoPull'])->name('absensi.toggle-auto');
Route::get('/absensi/cetak', [AbsensiController::class, 'cetakLaporan'])->name('absensi.cetak'); // <-- RUTE BARU UNTUK CETAK LAPORAN BULANAN

// 5. Rute Manajemen Karyawan
Route::get('/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
Route::post('/karyawan/store', [KaryawanController::class, 'store'])->name('karyawan.store');
Route::delete('/karyawan/{id}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy');
Route::post('/karyawan/sync-mesin', [KaryawanController::class, 'syncDariMesin'])->name('karyawan.sync-mesin');

// 6. Rute Menu Pengaturan & Pusat Kontrol SDK Mesin
Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
Route::post('/pengaturan/clear-log', [PengaturanController::class, 'clearMachineLogs'])->name('pengaturan.clear');
Route::post('/pengaturan/hapus-user', [PengaturanController::class, 'hapusUserDariMesin'])->name('pengaturan.hapus-user');
Route::post('/pengaturan/sync-time', [PengaturanController::class, 'synchronizeDeviceTime'])->name('pengaturan.sync-time');
Route::post('/pengaturan/restart', [PengaturanController::class, 'restartMachine'])->name('pengaturan.restart');
Route::post('/pengaturan/upload-fp', [PengaturanController::class, 'uploadSidikJariManual'])->name('pengaturan.upload-fp');
Route::post('/pengaturan/hapus-fp', [PengaturanController::class, 'hapusSidikJariManual'])->name('pengaturan.hapus-fp');
