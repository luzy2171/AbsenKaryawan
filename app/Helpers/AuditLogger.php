<?php

namespace App\Helpers;

use App\Models\AuditLog;

class AuditLogger
{
    /**
     * Log Login Activity
     */
    public static function login(string $username, string $status = 'success')
    {
        AuditLog::log(
            action: 'login',
            module: 'auth',
            description: $status === 'success' 
                ? "User {$username} berhasil login" 
                : "Percobaan login gagal untuk {$username}",
            status: $status
        );
    }

    /**
     * Log Logout Activity
     */
    public static function logout()
    {
        AuditLog::log(
            action: 'logout',
            module: 'auth',
            description: auth()->user()->name . " melakukan logout"
        );
    }

    /**
     * Log Karyawan Actions
     */
    public static function karyawanCreated($karyawan)
    {
        AuditLog::log(
            action: 'create',
            module: 'karyawan',
            description: "Menambahkan karyawan baru: {$karyawan->nama}",
            newValues: $karyawan->toArray()
        );
    }

    public static function karyawanDeleted($karyawan)
    {
        AuditLog::log(
            action: 'delete',
            module: 'karyawan',
            description: "Menghapus karyawan: {$karyawan->nama}",
            oldValues: $karyawan->toArray()
        );
    }

    public static function karyawanSynced($count)
    {
        AuditLog::log(
            action: 'sync',
            module: 'karyawan',
            description: "Sinkronisasi {$count} karyawan dari mesin absensi"
        );
    }

    /**
     * Log Absensi Actions
     */
    public static function absensiPulled($count)
    {
        AuditLog::log(
            action: 'pull',
            module: 'absensi',
            description: "Menarik {$count} data absensi dari mesin"
        );
    }

    public static function absensiExported($format, $count)
    {
        AuditLog::log(
            action: 'export',
            module: 'absensi',
            description: "Export {$count} data absensi ke format {$format}"
        );
    }

    public static function autoPullToggled($status)
    {
        AuditLog::log(
            action: 'toggle',
            module: 'absensi',
            description: "Mengubah auto-pull menjadi: " . ($status ? 'AKTIF' : 'NONAKTIF')
        );
    }

    /**
     * Log Settings Actions
     */
    public static function settingsUpdated($oldSettings, $newSettings)
    {
        AuditLog::log(
            action: 'update',
            module: 'settings',
            description: "Mengubah pengaturan jam kerja dan parameter aplikasi",
            oldValues: $oldSettings,
            newValues: $newSettings
        );
    }

    /**
     * Log Machine Control Actions
     */
    public static function machineClearLog()
    {
        AuditLog::log(
            action: 'clear_log',
            module: 'machine',
            description: "Membersihkan log transaksi mesin absensi"
        );
    }

    public static function machineSync()
    {
        AuditLog::log(
            action: 'sync_time',
            module: 'machine',
            description: "Sinkronisasi waktu mesin dengan server"
        );
    }

    public static function machineRestart()
    {
        AuditLog::log(
            action: 'restart',
            module: 'machine',
            description: "Merestart mesin absensi fisik"
        );
    }

    public static function machineUserDeleted($userId)
    {
        AuditLog::log(
            action: 'delete_user',
            module: 'machine',
            description: "Menghapus user ID {$userId} dari mesin absensi"
        );
    }

    public static function machineUploadFingerprint($userId, $fingerId)
    {
        AuditLog::log(
            action: 'upload_fp',
            module: 'machine',
            description: "Upload sidik jari manual - User ID: {$userId}, Finger ID: {$fingerId}"
        );
    }

    public static function machineDeleteFingerprint($userId, $fingerId)
    {
        AuditLog::log(
            action: 'delete_fp',
            module: 'machine',
            description: "Hapus sidik jari - User ID: {$userId}, Finger ID: {$fingerId}"
        );
    }

    /**
     * Log User Management Actions
     */
    public static function userCreated($user)
    {
        AuditLog::log(
            action: 'create',
            module: 'users',
            description: "Membuat user baru: {$user->username} dengan role {$user->role}",
            newValues: ['username' => $user->username, 'role' => $user->role, 'name' => $user->name]
        );
    }

    public static function userUpdated($user, $oldData)
    {
        AuditLog::log(
            action: 'update',
            module: 'users',
            description: "Mengubah data user: {$user->username}",
            oldValues: $oldData,
            newValues: ['username' => $user->username, 'role' => $user->role, 'name' => $user->name]
        );
    }

    public static function userDeleted($user)
    {
        AuditLog::log(
            action: 'delete',
            module: 'users',
            description: "Menghapus user: {$user->username}",
            oldValues: ['username' => $user->username, 'role' => $user->role, 'name' => $user->name]
        );
    }
}
