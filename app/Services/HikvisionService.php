<?php

namespace App\Services;

class HikvisionService
{
    protected $ip;
    protected $user;
    protected $pass;
    protected $port;

    public function __construct()
    {
        $this->ip = '10.10.10.79';
        $this->user = 'admin';
        $this->pass = 'hik12345';
        $this->port = 80;
    }

    public function request($endpoint, $method = 'GET', $data = null)
    {
        $url = "http://{$this->ip}:{$this->port}{$endpoint}";
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST | CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->user}:{$this->pass}");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); 

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'body'      => $response
        ];
    }

    public function getAllUsers()
    {
        $data = json_encode([
            "UserInfoSearchCond" => [
                "searchID" => "1",
                "searchResultPosition" => 0,
                "maxResults" => 1000 
            ]
        ]);
        
        $res = $this->request('/ISAPI/AccessControl/UserInfo/Search?format=json', 'POST', $data);
        if ($res['http_code'] != 200) return [];
        
        $userData = json_decode($res['body'], true);
        $users = $userData['UserInfoSearch']['UserInfo'] ?? [];
        
        $formattedUsers = [];
        foreach ($users as $u) {
            $pin = $u['employeeNo'] ?? '';
            $name = $u['name'] ?? '';
            
            if ($pin) {
                $formattedUsers[] = [
                    'user_id'   => $pin,
                    'pin'       => $pin,
                    'name'      => $name,
                    'privilege' => 'User'
                ];
            }
        }
        return $formattedUsers;
    }

    public function downloadLogDenganNama()
    {
        $startTime = date('Y-m-d\T00:00:00P', strtotime('-1 months')); 
        $endTime = date('Y-m-d\T23:59:59P');
        
        $data = json_encode([
            "AcsEventCond" => [
                "searchID" => "1",
                "searchResultPosition" => 0,
                "maxResults" => 1000, 
                "major" => 5, 
                "minor" => 0, 
                "startTime" => $startTime,
                "endTime" => $endTime
            ]
        ]);

        $res = $this->request('/ISAPI/AccessControl/AcsEvent?format=json', 'POST', $data);
        if ($res['http_code'] != 200) return [];
        
        $logData = json_decode($res['body'], true);
        $events = $logData['AcsEvent']['InfoList'] ?? [];
        
        $logs = [];
        foreach ($events as $evt) {
            $pin = $evt['employeeNoString'] ?? '';
            $timeStr = $evt['time'] ?? ''; 
            
            if ($pin && $timeStr) {
                $timestamp = strtotime($timeStr);
                $logs[] = [
                    'pin'     => $pin,
                    'name'    => $evt['name'] ?? '-',
                    'nama'    => $evt['name'] ?? '-',
                    'tanggal' => date('Y-m-d', $timestamp),
                    'waktu'   => date('H:i:s', $timestamp),
                    'datetime'=> date('Y-m-d H:i:s', $timestamp),
                    'date'    => date('Y-m-d', $timestamp),
                    'time'    => date('H:i:s', $timestamp),
                    'verify'  => 'Wajah/Kartu',
                    'verified'=> 'Wajah/Kartu',
                    'status'  => 'Berhasil',
                ];
            }
        }
        return array_reverse($logs); 
    }

    public function downloadLog()
    {
        return $this->downloadLogTigaBulan();
    }

    public function downloadLogTigaBulan()
    {
        $startTime = date('Y-m-d\T00:00:00P', strtotime('-3 months')); 
        $endTime = date('Y-m-d\T23:59:59P');
        
        $data = json_encode([
            "AcsEventCond" => [
                "searchID" => "1",
                "searchResultPosition" => 0,
                "maxResults" => 3000, 
                "major" => 5, 
                "minor" => 0, 
                "startTime" => $startTime,
                "endTime" => $endTime
            ]
        ]);

        $res = $this->request('/ISAPI/AccessControl/AcsEvent?format=json', 'POST', $data);
        if ($res['http_code'] != 200) return [];
        
        $logData = json_decode($res['body'], true);
        $events = $logData['AcsEvent']['InfoList'] ?? [];
        
        $logs = [];
        foreach ($events as $evt) {
            $pin = $evt['employeeNoString'] ?? '';
            $timeStr = $evt['time'] ?? '';
            
            if ($pin && $timeStr) {
                $timestamp = strtotime($timeStr);
                $logs[] = [
                    'pin'      => $pin,
                    'datetime' => date('Y-m-d H:i:s', $timestamp),
                    'verified' => 'Wajah/Kartu',
                    'status'   => 'Berhasil',
                ];
            }
        }
        return $logs;
    }

    public function getFingerprintTemplate($id, $fn)
    {
        return []; 
    }

    public function clearLogData()
    {
        return "Penghapusan log via web tidak didukung untuk mesin Hikvision. Silakan hapus lewat iVMS-4200.";
    }

    public function hapusUser($id)
    {
        $data = json_encode([
            "UserInfoDetail" => [
                "mode" => "byEmployeeNo",
                "EmployeeNoList" => [
                    ["employeeNo" => (string)$id]
                ]
            ]
        ]);
        
        $res = $this->request('/ISAPI/AccessControl/UserInfoDetail/Delete?format=json', 'PUT', $data);
        if ($res['http_code'] == 200) return "Sukses";
        return "Koneksi Gagal / Gagal Menghapus";
    }

    public function syncTime()
    {
        $dateTime = date('Y-m-d\TH:i:sP');
        $data = json_encode([
            "Time" => [
                "timeMode" => "manual",
                "localTime" => $dateTime
            ]
        ]);
        
        $res = $this->request('/ISAPI/System/time?format=json', 'PUT', $data);
        if ($res['http_code'] == 200) return "Sukses";
        return "Koneksi Gagal / Waktu tidak sinkron";
    }

    public function restartDevice()
    {
        $res = $this->request('/ISAPI/System/reboot', 'PUT');
        if ($res['http_code'] == 200) return "Sukses";
        return "Koneksi Gagal";
    }

    public function uploadNama($id, $nama)
    {
        $data = json_encode([
            "UserInfo" => [
                "employeeNo" => (string)$id,
                "name" => $nama,
                "userType" => "normal"
            ]
        ]);
        
        $res = $this->request('/ISAPI/AccessControl/UserInfo/Record?format=json', 'POST', $data);
        if ($res['http_code'] == 200) return "Sukses";
        return "Koneksi Gagal";
    }

    public function uploadSidikJari($id, $fn, $template)
    {
        return "Gagal: Fitur ini perlu menggunakan SDK Hikvision Desktop";
    }

    public function deleteSidikJari($id, $fn)
    {
        return "Gagal: Fitur ini perlu menggunakan SDK Hikvision Desktop";
    }
}
