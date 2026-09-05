<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Admin\DatabaseMaintenanceController;
use App\Http\Controllers\LeaveController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ===== AUTHENTIKASI =====
// Halaman login
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit')->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Semua halaman utama hanya bisa diakses setelah login
Route::middleware('auth')->group(function () {

    // 1. Rute untuk Halaman Utama / Otomatis masuk ke Dashboard
    Route::get('/', function () {
        return redirect('/dashboard');
    });

    // 2. Rute untuk Halaman Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');

    // 3. Rute untuk Halaman Absensi (Tabel Catatan Absensi)
    Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi.index');

    // 4. Rute untuk Proses Tarik Data, Sakelar Otomatis, Cetak Laporan, & Export Excel
    Route::post('/absensi/tarik', [AbsensiController::class, 'tarikDataDariMesin'])->name('absensi.tarik');
    Route::post('/absensi/toggle-auto-pull', [AbsensiController::class, 'toggleAutoPull'])->name('absensi.toggle-auto');
    Route::get('/absensi/cetak', [AbsensiController::class, 'cetakLaporan'])->name('absensi.cetak');
    Route::get('/absensi/export-excel', [AbsensiController::class, 'exportExcel'])->name('absensi.export-excel');
    Route::post('/absensi/lembur', [AbsensiController::class, 'storeLembur'])->name('absensi.lembur.store');
    Route::delete('/absensi/lembur/{id}', [AbsensiController::class, 'destroyLembur'])->name('absensi.lembur.destroy');

    // 5. Rute Manajemen Karyawan
    Route::get('/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
    Route::post('/karyawan/store', [KaryawanController::class, 'store'])->name('karyawan.store');
    Route::delete('/karyawan/{id}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy');
    Route::post('/karyawan/sync-mesin', [KaryawanController::class, 'syncDariMesin'])->name('karyawan.sync-mesin');

    // 6. Rute Manajemen Kontrol Interface Jam Kerja Dinamis (Khusus Superadmin)
    Route::middleware('superadmin')->group(function () {
        Route::get('/admin/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/admin/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    // 7. Rute Menu Pengaturan & Pusat Kontrol SDK Mesin (Khusus Superadmin)
    Route::middleware('superadmin')->prefix('pengaturan')->name('pengaturan.')->group(function () {
        Route::get('/', [PengaturanController::class, 'index'])->name('index');
        Route::post('/add-machine', [PengaturanController::class, 'storeMachine'])->name('machine.store');
        Route::put('/machine/{id}/status', [PengaturanController::class, 'updateMachineStatus'])->name('machine.update-status');
        Route::delete('/machine/{id}', [PengaturanController::class, 'destroyMachine'])->name('machine.destroy');
        Route::post('/machine/{id}/ping', [PengaturanController::class, 'pingMachine'])->name('machine.ping');
        Route::post('/machine/{id}/default', [PengaturanController::class, 'setDefaultMachine'])->name('machine.default');
        Route::post('/clear-log', [PengaturanController::class, 'clearMachineLogs'])->name('clear');
        Route::post('/hapus-user', [PengaturanController::class, 'hapusUserDariMesin'])->name('hapus-user');
        Route::post('/sync-time', [PengaturanController::class, 'synchronizeDeviceTime'])->name('sync-time');
        Route::post('/restart', [PengaturanController::class, 'restartMachine'])->name('restart');
        Route::post('/upload-fp', [PengaturanController::class, 'uploadSidikJariManual'])->name('upload-fp');
        Route::post('/hapus-fp', [PengaturanController::class, 'hapusSidikJariManual'])->name('hapus-fp');
    });

    // ===== MANAJEMEN USER / ADMIN (Khusus Superadmin) =====
    Route::prefix('admin/users')->name('admin.users.')->middleware('superadmin')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/store', [UserController::class, 'store'])->name('store');
        Route::post('/update/{id}', [UserController::class, 'update'])->name('update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    });

    
    // ===== MANAJEMEN CUTI & IZIN (Khusus Admin & Superadmin) =====
    Route::prefix('admin/leaves')->name('admin.leaves.')->middleware('admin')->group(function () {
        Route::get('/', [LeaveController::class, 'index'])->name('index');
        Route::post('/', [LeaveController::class, 'store'])->name('store');
        Route::delete('/{id}', [LeaveController::class, 'destroy'])->name('destroy');
    });

    // ===== MANAJEMEN DATABASE (Khusus Superadmin) =====
    Route::prefix('admin/maintenance')->name('admin.maintenance.')->middleware('superadmin')->group(function () {
        Route::get('/', [DatabaseMaintenanceController::class, 'index'])->name('index');
        Route::post('/purge', [DatabaseMaintenanceController::class, 'purgeData'])->name('purge');
    });

    // ===== AUDIT LOGS (Khusus Superadmin) =====
    Route::prefix('admin/audit-logs')->name('admin.audit-logs.')->middleware('superadmin')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/export', [AuditLogController::class, 'export'])->name('export');
        Route::get('/{id}', [AuditLogController::class, 'show'])->name('show');
    });
});
