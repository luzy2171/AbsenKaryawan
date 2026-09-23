<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SolutionX100CService
{
    protected $ip;
    protected $port;
    protected $stream;
    protected $sessionId = 0;

    const USHRT_MAX = 65535;

    const CMD_CONNECT = 1000;
    const CMD_EXIT = 1001;
    const CMD_ENABLE_DEVICE = 1002;
    const CMD_DISABLE_DEVICE = 1003;
    const CMD_RESTART = 1004;
    const CMD_POWEROFF = 1005;
    const CMD_SLEEP = 1006;
    const CMD_RESUME = 1007;
    const CMD_TESTVOICE = 1017;
    const CMD_WRITE_LCD = 66;
    const CMD_CLEAR_LCD = 67;

    const CMD_ACK_OK = 2000;
    const CMD_ACK_ERROR = 2001;
    const CMD_ACK_DATA = 2002;
    const CMD_ACK_UNAUTH = 2005;

    const CMD_PREPARE_DATA = 1500;
    const CMD_DATA = 1501;
    const CMD_FREE_DATA = 1502;

    const CMD_USER_TEMP_RRQ = 9;
    const CMD_ATT_LOG_RRQ = 13;
    const CMD_CLEAR_DATA = 14;
    const CMD_CLEAR_ATT_LOG = 15;
    const CMD_GET_TIME = 201;
    const CMD_SET_TIME = 202;
    const CMD_VERSION = 1100;
    const CMD_DEVICE = 11;

    const CMD_SET_USER = 8;
    const CMD_DELETE_USER = 18;

    const FCT_USER = 5;
    const COMMAND_TYPE_GENERAL = 'general';
    const COMMAND_TYPE_DATA = 'data';

    public function __construct()
    {
        $this->ip = env('ZKTECO_IP', '192.168.1.201');
        $this->port = env('ZKTECO_PORT', 4370);
    }

    public function setConnection($ip, $port = 4370)
    {
        $this->ip = $ip;
        $this->port = $port;
        return $this;
    }

    protected function connectStream()
    {
        $address = "{$this->ip}:{$this->port}";
        $stream = stream_socket_client($address, $errno, $errstr, 30);

        if (!$stream) {
            Log::error("Solution X100C connect error: {$errno} - {$errstr}");
            return false;
        }

        stream_set_timeout($stream, 10);
        return $stream;
    }

    protected function sendCommand($command, $commandString = '', $type = self::COMMAND_TYPE_GENERAL)
    {
        if (!$this->stream) {
            $this->stream = $this->connectStream();
            if (!$this->stream) return false;
        }

        $chksum = $this->createChkSumForPacket($command, 0, $this->sessionId, -1 + self::USHRT_MAX, $commandString);
        $replyId = -1 + self::USHRT_MAX;

        $buf = $this->buildPacket($command, $chksum, $this->sessionId, $replyId, $commandString);
        fwrite($this->stream, $buf);

        $response = fread($this->stream, 1024);
        if (empty($response)) return false;

        $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6', $response);
        $session = hexdec($u['h6'] . $u['h5']);

        if ($type === self::COMMAND_TYPE_GENERAL && $this->sessionId === $session) {
            return substr($response, 8);
        } elseif ($type === self::COMMAND_TYPE_DATA && !empty($session)) {
            $this->sessionId = $session;
            return $session;
        }

        return false;
    }

    protected function buildPacket($command, $chksum, $sessionId, $replyId, $commandString)
    {
        $buf = pack('SSSS', $command, $chksum, $sessionId, $replyId) . $commandString;

        $bytes = unpack('C*', $buf);
        $computedChk = $this->calcChecksum($bytes);

        $replyId += 1;
        if ($replyId >= self::USHRT_MAX) {
            $replyId -= self::USHRT_MAX;
        }

        return pack('SSSS', $command, $computedChk, $sessionId, $replyId) . $commandString;
    }

    protected function createChkSumForPacket($command, $chksum, $sessionId, $replyId, $commandString)
    {
        $buf = pack('SSSS', $command, $chksum, $sessionId, $replyId) . $commandString;
        $bytes = unpack('C*', $buf);
        return $this->calcChecksum($bytes);
    }

    protected function calcChecksum($bytes)
    {
        $l = count($bytes);
        $chksum = 0;
        $j = 1;
        $i = $l;

        while ($i > 1) {
            $val = unpack('S', pack('C2', $bytes[$j], $bytes[$j + 1]));
            $chksum += $val[1];
            if ($chksum > self::USHRT_MAX) {
                $chksum -= self::USHRT_MAX;
            }
            $i -= 2;
            $j += 2;
        }

        if ($i) {
            $chksum += $bytes[$l];
        }

        while ($chksum > self::USHRT_MAX) {
            $chksum -= self::USHRT_MAX;
        }

        if ($chksum > 0) {
            $chksum = -$chksum;
        } else {
            $chksum = abs($chksum);
        }

        $chksum -= 1;
        while ($chksum < 0) {
            $chksum += self::USHRT_MAX;
        }

        return $chksum;
    }

    protected function readDataResponse()
    {
        if (!$this->stream) return '';

        $header = fread($this->stream, 8);
        if (strlen($header) < 8) return '';

        $u = unpack('H2h1/H2h2/H2h3/H2h4', $header);
        $command = hexdec($u['h2'] . $u['h1']);

        if ($command !== self::CMD_PREPARE_DATA) return '';

        $size = hexdec($u['h4'] . $u['h3'] . $u['h2'] . $u['h1']);
        $data = '';
        $received = 0;

        while ($received < $size) {
            $chunk = fread($this->stream, 1024);
            if ($chunk === false || $chunk === '') break;
            $data .= $chunk;
            $received += strlen($chunk);
        }

        @fread($this->stream, 1024);

        return $data;
    }

    protected function checkValid($reply)
    {
        if (strlen($reply) < 8) return false;
        $u = unpack('H2h1/H2h2', $reply);
        $command = hexdec($u['h2'] . $u['h1']);
        return ($command === self::CMD_ACK_OK || $command === self::CMD_ACK_UNAUTH);
    }

    public function connect()
    {
        $this->stream = $this->connectStream();
        if (!$this->stream) return false;

        $reply = $this->sendCommand(self::CMD_CONNECT, '', self::COMMAND_TYPE_GENERAL);
        if ($reply === false) {
            $this->disconnect();
            return false;
        }

        $this->sessionId = 1;
        return true;
    }

    public function disconnect()
    {
        if ($this->stream && $this->sessionId > 0) {
            $this->sendCommand(self::CMD_EXIT, '', self::COMMAND_TYPE_GENERAL);
        }
        if ($this->stream) {
            fclose($this->stream);
            $this->stream = null;
        }
        $this->sessionId = 0;
        return true;
    }

    public function getAllUsers()
    {
        if (!$this->connect()) return [];

        $session = $this->sendCommand(
            self::CMD_USER_TEMP_RRQ,
            chr(self::FCT_USER),
            self::COMMAND_TYPE_DATA
        );

        if ($session === false) {
            $this->disconnect();
            return [];
        }

        $userData = $this->readDataResponse();
        $users = [];

        if (!empty($userData)) {
            $userData = substr($userData, 11);

            while (strlen($userData) >= 72) {
                $hex = bin2hex(substr($userData, 0, 72));

                $u1 = hexdec(substr($hex, 2, 2));
                $u2 = hexdec(substr($hex, 4, 2));
                $uid = $u1 + ($u2 * 256);

                $userid = hex2bin(substr($hex, 98, 72));
                $userid = rtrim($userid, "\0");

                $name = hex2bin(substr($hex, 24, 74));
                $name = rtrim($name, "\0");
                $name = mb_convert_encoding($name, 'UTF-8', 'UTF-8');

                $password = hex2bin(substr($hex, 8, 16));
                $password = rtrim($password, "\0");

                $role = hexdec(substr($hex, 6, 2));

                if ($name === '') {
                    $name = $userid;
                }

                $users[] = [
                    'user_id' => $uid,
                    'pin'     => (string) $userid,
                    'name'    => $name,
                    'privilege' => $role == 14 ? 'Super Admin' : 'User'
                ];

                $userData = substr($userData, 72);
            }
        }

        $this->disconnect();
        return $users;
    }

    public function downloadLogTigaBulan()
    {
        if (!$this->connect()) return [];

        $session = $this->sendCommand(
            self::CMD_ATT_LOG_RRQ,
            '',
            self::COMMAND_TYPE_DATA
        );

        if ($session === false) {
            $this->disconnect();
            return [];
        }

        $attData = $this->readDataResponse();
        $logs = [];
        $threeMonthsAgo = strtotime('-3 months');

        if (!empty($attData)) {
            $attData = substr($attData, 10);

            while (strlen($attData) >= 39) {
                $hex = bin2hex(substr($attData, 0, 39));

                $u1 = hexdec(substr($hex, 4, 2));
                $u2 = hexdec(substr($hex, 6, 2));
                $uid = $u1 + ($u2 * 256);

                $id = hex2bin(substr($hex, 8, 18));
                $id = rtrim($id, "\0");

                $state = hexdec(substr($hex, 56, 2));
                $timeVal = hexdec(strrev(substr($hex, 58, 8)));
                $timestamp = $this->decodeTime($timeVal);

                if (strtotime($timestamp) >= $threeMonthsAgo) {
                    $logs[] = [
                        'pin'      => (string) $id,
                        'datetime' => $timestamp,
                        'verified' => (string) $state,
                        'status'   => 'Berhasil',
                        'source'   => 'Solution X100C'
                    ];
                }

                $attData = substr($attData, 40);
            }
        }

        $this->disconnect();
        return $logs;
    }

    public function clearLogData()
    {
        if (!$this->connect()) return "Koneksi Gagal";

        $this->sendCommand(self::CMD_CLEAR_ATT_LOG, '', self::COMMAND_TYPE_GENERAL);
        $this->disconnect();

        return "Sukses menghapus log absensi di mesin Solution X100C.";
    }

    public function hapusUser($id)
    {
        if (!is_numeric($id)) return "Koneksi Gagal";

        if (!$this->connect()) return "Koneksi Gagal";

        $uid = (int) $id;
        $byte1 = chr($uid % 256);
        $byte2 = chr(intval($uid / 256));
        $commandString = $byte1 . $byte2;

        $this->sendCommand(self::CMD_DELETE_USER, $commandString, self::COMMAND_TYPE_GENERAL);
        $this->disconnect();

        return "Sukses";
    }

    public function syncTime()
    {
        if (!$this->connect()) return "Koneksi Gagal";

        $now = date('Y-m-d H:i:s');
        $timeVal = $this->encodeTime($now);

        $byte1 = chr($timeVal & 0xFF);
        $byte2 = chr(($timeVal >> 8) & 0xFF);
        $byte3 = chr(($timeVal >> 16) & 0xFF);
        $byte4 = chr(($timeVal >> 24) & 0xFF);

        $this->sendCommand(self::CMD_SET_TIME, $byte1 . $byte2 . $byte3 . $byte4, self::COMMAND_TYPE_GENERAL);
        $this->disconnect();

        return "Waktu berhasil disinkronkan: {$now}";
    }

    public function restartDevice()
    {
        if (!$this->connect()) return "Koneksi Gagal";

        $this->sendCommand(self::CMD_RESTART, chr(0) . chr(0), self::COMMAND_TYPE_GENERAL);
        $this->disconnect();

        return "Sukses";
    }

    public function uploadNama($id, $nama)
    {
        if (!is_numeric($id) || empty($nama)) return "Koneksi Gagal";

        if (!$this->connect()) return "Koneksi Gagal";

        $uid = (int) $id;
        $userid = str_pad((string) $id, 9, "\0");
        $name = str_pad($nama, 24, "\0");
        $password = str_repeat("\0", 8);
        $cardno = str_repeat("\0", 4);

        $byte1 = chr($uid % 256);
        $byte2 = chr(intval($uid / 256));
        $role = chr(0);

        $commandString = $byte1 . $byte2 . $role . $password . $name . $cardno . chr(1) . str_repeat("\0", 8) . str_repeat("\0", 15);

        $this->sendCommand(self::CMD_SET_USER, $commandString, self::COMMAND_TYPE_GENERAL);
        $this->disconnect();

        return "Sukses";
    }

    protected function encodeTime($t)
    {
        $timestamp = strtotime($t);
        $year  = (int) date('Y', $timestamp);
        $month = (int) date('m', $timestamp);
        $day   = (int) date('d', $timestamp);
        $hour  = (int) date('H', $timestamp);
        $min   = (int) date('i', $timestamp);
        $sec   = (int) date('s', $timestamp);

        $d = (($year % 100) * 12 * 31 + (($month - 1) * 31) + $day - 1) * (24 * 60 * 60)
           + ($hour * 60 + $min) * 60 + $sec;

        return $d;
    }

    protected function decodeTime($t)
    {
        $second = $t % 60;
        $t = intval($t / 60);

        $minute = $t % 60;
        $t = intval($t / 60);

        $hour = $t % 24;
        $t = intval($t / 24);

        $day = $t % 31 + 1;
        $t = intval($t / 31);

        $month = $t % 12 + 1;
        $t = intval($t / 12);

        $year = $t + 2000;

        return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
    }
}
