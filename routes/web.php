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
use App\Http\Controllers\CutiControlController;
use App\Http\Controllers\MesinAbsensiController;


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

    Route::post('/profile/update', [DashboardController::class, 'updateProfile'])->name('profile.update');

    // 2. Rute View-Only (Approver dan atas)
    Route::middleware('approver')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');
        Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi.index');
        Route::get('/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
        Route::get('admin/leaves', [LeaveController::class, 'index'])->name('admin.leaves.index');
    });

    // 3. Rute Khusus Approver Murni & Superadmin
    Route::middleware('true_approver')->group(function () {
        Route::get('admin/cuti-control', [CutiControlController::class, 'index'])->name('admin.cuti.control');
        Route::put('admin/cuti-control/{id}', [CutiControlController::class, 'update'])->name('admin.cuti.control.update');
        Route::put('admin/leaves/{id}/approve', [LeaveController::class, 'approve'])->name('admin.leaves.approve');
        Route::delete('admin/leaves/{id}', [LeaveController::class, 'destroy'])->name('admin.leaves.destroy');
        Route::delete('/karyawan/{id}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy');
        Route::get('admin/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs.index');
        Route::get('admin/audit-logs/export', [AuditLogController::class, 'export'])->name('admin.audit-logs.export');
        Route::get('admin/audit-logs/{id}', [AuditLogController::class, 'show'])->name('admin.audit-logs.show');
    });

    // 4. Aksi Operasional (Admin ke atas)
    Route::middleware('admin')->group(function () {
        Route::get('admin/mesin-absensi', [MesinAbsensiController::class, 'index'])->name('admin.mesin.index');
        Route::post('admin/mesin-absensi/device', [MesinAbsensiController::class, 'storeDevice'])->name('admin.mesin.device.store');
        Route::put('admin/mesin-absensi/device/{id}', [MesinAbsensiController::class, 'updateDevice'])->name('admin.mesin.device.update');
        Route::delete('admin/mesin-absensi/device/{id}', [MesinAbsensiController::class, 'destroyDevice'])->name('admin.mesin.device.destroy');
        Route::post('admin/mesin-absensi/device/{id}/ping', [MesinAbsensiController::class, 'pingDevice'])->name('admin.mesin.device.ping');
        Route::get('admin/mesin-absensi/door/events', [MesinAbsensiController::class, 'getHikEvents'])->name('admin.mesin.door.events');
        Route::post('admin/mesin-absensi/door/open', [MesinAbsensiController::class, 'openDoor'])->name('admin.mesin.door.open');
        Route::post('admin/mesin-absensi/tarik', [MesinAbsensiController::class, 'tarikDataAll'])->name('admin.mesin.tarik');
        Route::post('admin/mesin-absensi/kirim', [MesinAbsensiController::class, 'kirimData'])->name('admin.mesin.kirim');
        Route::delete('admin/mesin-absensi/hapus/{mesin}/{pin}', [MesinAbsensiController::class, 'hapusData'])->name('admin.mesin.hapus');
        Route::put('admin/mesin-absensi/karyawan/{id}', [MesinAbsensiController::class, 'updateKaryawan'])->name('admin.mesin.karyawan.update');
        Route::delete('admin/mesin-absensi/karyawan/{id}/db', [MesinAbsensiController::class, 'hapusKaryawanDB'])->name('admin.mesin.karyawan.db.destroy');
        Route::post('/absensi/tarik', [AbsensiController::class, 'tarikDataDariMesin'])->name('absensi.tarik');
        Route::post('/absensi/toggle-auto-pull', [AbsensiController::class, 'toggleAutoPull'])->name('absensi.toggle-auto');
        Route::get('/absensi/cetak', [AbsensiController::class, 'cetakLaporan'])->name('absensi.cetak');
        Route::get('/absensi/export-excel', [AbsensiController::class, 'exportExcel'])->name('absensi.export-excel');
        Route::post('/absensi/lembur', [AbsensiController::class, 'storeLembur'])->name('absensi.lembur.store');
        Route::delete('/absensi/lembur/{id}', [AbsensiController::class, 'destroyLembur'])->name('absensi.lembur.destroy');
        Route::post('/karyawan/store', [KaryawanController::class, 'store'])->name('karyawan.store');
        Route::post('/karyawan/sync-mesin', [KaryawanController::class, 'syncDariMesin'])->name('karyawan.sync-mesin');
        Route::post('admin/leaves', [LeaveController::class, 'store'])->name('admin.leaves.store');
    });

    // 5. Rute Khusus Superadmin
    Route::middleware('superadmin')->group(function () {
        Route::get('/admin/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/admin/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::prefix('pengaturan')->name('pengaturan.')->group(function () {
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
        Route::prefix('admin/users')->name('admin.users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/store', [UserController::class, 'store'])->name('store');
            Route::post('/update/{id}', [UserController::class, 'update'])->name('update');
            Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
        });
        Route::prefix('admin/maintenance')->name('admin.maintenance.')->group(function () {
            Route::get('/', [DatabaseMaintenanceController::class, 'index'])->name('index');
            Route::post('/purge', [DatabaseMaintenanceController::class, 'purgeData'])->name('purge');
        });
    });
});
