<?php

namespace App\Services;

use Rats\Zkteco\Lib\ZKTeco;
use Illuminate\Support\Facades\Log;

class ZktecoService
{
    protected $ip;
    protected $port;

    public function __construct()
    {
        // Default IP for Solution X100C / ZKTeco usually 192.168.1.201
        $this->ip = env('ZKTECO_IP', '192.168.1.201');
        $this->port = env('ZKTECO_PORT', 4370);
    }

    public function connect()
    {
        $zk = new ZKTeco($this->ip, $this->port);
        if ($zk->connect()) {
            return $zk;
        }
        return false;
    }

    public function getAllUsers()
    {
        $zk = $this->connect();
        if (!$zk) return [];
        
        $users = $zk->getUser();
        $formattedUsers = [];
        
        foreach ($users as $u) {
            $formattedUsers[] = [
                'user_id'   => $u['userid'],
                'pin'       => $u['userid'],
                'name'      => $u['name'],
                'privilege' => $u['role'] == '14' ? 'Super Admin' : 'User'
            ];
        }
        
        $zk->disconnect();
        return $formattedUsers;
    }

    public function downloadLogTigaBulan()
    {
        $zk = $this->connect();
        if (!$zk) return [];

        $attendances = $zk->getAttendance();
        $zk->disconnect();

        $logs = [];
        $threeMonthsAgo = strtotime('-3 months');

        foreach ($attendances as $att) {
            $timestamp = strtotime($att['timestamp']);
            
            if ($timestamp >= $threeMonthsAgo) {
                $logs[] = [
                    'pin'      => (string) $att['id'],
                    'datetime' => $att['timestamp'],
                    'verified' => 'Fingerprint/Card/Password',
                    'status'   => 'Berhasil',
                    'source'   => 'Solution X100C'
                ];
            }
        }

        return $logs;
    }

    public function clearLogData()
    {
        $zk = $this->connect();
        if (!$zk) return "Koneksi Gagal";
        
        $zk->clearAttendance();
        $zk->disconnect();
        
        return "Sukses menghapus log absensi di mesin Solution X100C.";
    }

    public function hapusUser($id)
    {
        $zk = $this->connect();
        if (!$zk) return "Koneksi Gagal";
        
        $zk->removeUser($id);
        $zk->disconnect();
        
        return "Sukses";
    }

    public function syncTime()
    {
        return "Fitur sinkronisasi waktu belum didukung penuh untuk ZKTeco via library ini.";
    }

    public function restartDevice()
    {
        $zk = $this->connect();
        if (!$zk) return "Koneksi Gagal";
        
        $zk->restart();
        
        return "Sukses";
    }

    public function uploadNama($id, $nama)
    {
        $zk = $this->connect();
        if (!$zk) return "Koneksi Gagal";
        
        $zk->setUser(1, $id, $nama, '', 0);
        $zk->disconnect();
        
        return "Sukses";
    }
}
