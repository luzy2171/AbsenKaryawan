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
            'login',
            'auth',
            $status === 'success'
                ? "User {$username} berhasil login"
                : "Percobaan login gagal untuk {$username}",
            null,
            null,
            $status
        );
    }

    /**
     * Log Custom Activity
     */
    public static function logCustom(string $action, string $description, string $module = 'izin', string $status = 'success', $newValues = null, $oldValues = null)
    {
        AuditLog::log(
            $action,
            $module,
            $description,
            $oldValues,
            $newValues,
            $status
        );
    }

    /**
     * Log Logout Activity
     */
    public static function logout()
    {
        $userName = auth()->user() ? auth()->user()->name : 'System';
        AuditLog::log('logout', 'auth', $userName . " melakukan logout");
    }

    /**
     * Log Karyawan Actions
     */
    public static function karyawanCreated($karyawan)
    {
        AuditLog::log(
            'create',
            'karyawan',
            "Menambahkan karyawan baru: {$karyawan->nama}",
            null,
            $karyawan->toArray()
        );
    }

    public static function karyawanDeleted($karyawan)
    {
        AuditLog::log(
            'delete',
            'karyawan',
            "Menghapus karyawan: {$karyawan->nama}",
            $karyawan->toArray()
        );
    }

    public static function karyawanSynced($count)
    {
        AuditLog::log('sync', 'karyawan', "Sinkronisasi {$count} karyawan dari mesin absensi");
    }

    /**
     * Log Absensi Actions
     */
    public static function absensiPulled($count)
    {
        AuditLog::log('pull', 'absensi', "Menarik {$count} data absensi dari mesin");
    }

    public static function absensiExported($format, $count)
    {
        AuditLog::log('export', 'absensi', "Export {$count} data absensi ke format {$format}");
    }

    public static function autoPullToggled($status)
    {
        AuditLog::log('toggle', 'absensi', "Mengubah auto-pull menjadi: " . ($status ? 'AKTIF' : 'NONAKTIF'));
    }

    /**
     * Log Settings Actions
     */
    public static function settingsUpdated($oldSettings, $newSettings)
    {
        AuditLog::log(
            'update',
            'settings',
            "Mengubah pengaturan jam kerja dan parameter aplikasi",
            $oldSettings,
            $newSettings
        );
    }

    /**
     * Log Machine Control Actions
     */
    public static function machineClearLog()
    {
        AuditLog::log('clear_log', 'machine', "Membersihkan log transaksi mesin absensi");
    }

    public static function machineSync()
    {
        AuditLog::log('sync_time', 'machine', "Sinkronisasi waktu mesin dengan server");
    }

    public static function machineRestart()
    {
        AuditLog::log('restart', 'machine', "Merestart mesin absensi fisik");
    }

    public static function machineUserDeleted($userId)
    {
        AuditLog::log('delete_user', 'machine', "Menghapus user ID {$userId} dari mesin absensi");
    }

    public static function machineAdded($ip, $name)
    {
        AuditLog::log('add_machine', 'machine', "Menambahkan perangkat mesin absensi baru: {$name} ({$ip})");
    }

    public static function machineDeleted($ip, $name)
    {
        AuditLog::log('delete_machine', 'machine', "Menghapus perangkat mesin absensi: {$name} ({$ip})");
    }

    public static function machineUploadFingerprint($userId, $fingerId)
    {
        AuditLog::log('upload_fp', 'machine', "Upload sidik jari manual - User ID: {$userId}, Finger ID: {$fingerId}");
    }

    public static function machineDeleteFingerprint($userId, $fingerId)
    {
        AuditLog::log('delete_fp', 'machine', "Hapus sidik jari - User ID: {$userId}, Finger ID: {$fingerId}");
    }

    /**
     * Log User Management Actions
     */
    public static function userCreated($user)
    {
        AuditLog::log(
            'create',
            'users',
            "Membuat user baru: {$user->username} dengan role {$user->role}",
            null,
            ['username' => $user->username, 'role' => $user->role, 'name' => $user->name]
        );
    }

    public static function userUpdated($user, $oldData)
    {
        AuditLog::log(
            'update',
            'users',
            "Mengubah data user: {$user->username}",
            $oldData,
            ['username' => $user->username, 'role' => $user->role, 'name' => $user->name]
        );
    }

    public static function userDeleted($user)
    {
        AuditLog::log(
            'delete',
            'users',
            "Menghapus user: {$user->username}",
            ['username' => $user->username, 'role' => $user->role, 'name' => $user->name]
        );
    }
}
