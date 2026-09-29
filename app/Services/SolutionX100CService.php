<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SolutionX100CService
{
    protected $ip;
    protected $port;
    protected $stream;
    protected $sessionId = 0;
    protected $replyId = 0;
    protected $lastConnectionSucceeded = false;

    const USHRT_MAX = 65535;
    const HEADER_LENGTH = 8;
    const MAX_CHUNK_SIZE = 1024;
    const USER_RECORD_SIZE = 72;
    const ATTENDANCE_RECORD_SIZE = 40;
    const TIME_EPOCH_YEAR = 2000;

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
        $this->port = (int) env('ZKTECO_PORT', 4370);
    }

    public function setConnection($ip, $port = 4370)
    {
        $this->ip = $ip;
        $this->port = (int) $port;
        $this->closeStream();
        $this->sessionId = 0;
        $this->replyId = 0;
        $this->lastConnectionSucceeded = false;

        return $this;
    }

    public function wasLastConnectionSuccessful(): bool
    {
        return $this->lastConnectionSucceeded;
    }

    protected function connectStream()
    {
        $address = "tcp://{$this->ip}:{$this->port}";
        $stream = @stream_socket_client($address, $errno, $errstr, 5, STREAM_CLIENT_CONNECT);

        if (!$stream) {
            Log::error("Solution X100C connect error: {$errno} - {$errstr}");
            return false;
        }

        stream_set_timeout($stream, 10);
        return $stream;
    }

    protected function sendCommand($command, $commandString = '', $type = self::COMMAND_TYPE_GENERAL)
    {
        if (!$this->ensureStream()) {
            return false;
        }

        $packet = $this->buildPacket($command, 0, $this->sessionId, $this->replyId, $commandString);
        if (!$this->writeExactly($packet)) {
            $this->closeStream();
            return false;
        }

        $header = $this->readHeader();
        if ($header === false) {
            $this->closeStream();
            return false;
        }

        if ($type === self::COMMAND_TYPE_DATA) {
            if ($header['command'] === self::CMD_ACK_DATA) {
                $header = $this->readHeader();
            }

            if ($header === false || $header['command'] !== self::CMD_PREPARE_DATA) {
                return false;
            }

            return $this->readDataResponse($header);
        }

        if ($header['session'] !== $this->sessionId || $header['command'] !== self::CMD_ACK_OK) {
            return false;
        }

        return true;
    }

    protected function buildPacket($command, $chksum, $sessionId, $replyId, $commandString)
    {
        $command &= self::USHRT_MAX;
        $sessionId &= self::USHRT_MAX;
        $replyId &= self::USHRT_MAX;
        $zeroed = pack('v4', $command, 0, $sessionId, $replyId) . $commandString;
        $computedChksum = $this->calcChecksum($zeroed);
        $nextReplyId = ($replyId + 1) & self::USHRT_MAX;
        $this->replyId = $nextReplyId;

        return pack('v4', $command, $computedChksum, $sessionId, $nextReplyId) . $commandString;
    }

    protected function createChkSumForPacket($command, $chksum, $sessionId, $replyId, $commandString)
    {
        $packet = pack('v4', $command, 0, $sessionId, $replyId) . $commandString;
        return $this->calcChecksum($packet);
    }

    protected function calcChecksum($packet)
    {
        if (strlen($packet) % 2 !== 0) {
            $packet .= "\0";
        }

        $sum = 0;
        $words = unpack('v*', $packet);
        foreach ($words as $word) {
            $sum = ($sum + $word) & self::USHRT_MAX;
        }

        return (~$sum) & self::USHRT_MAX;
    }

    protected function readHeader()
    {
        $header = $this->readExactly(self::HEADER_LENGTH);
        if ($header === false || !$this->checkValid($header)) {
            return false;
        }

        $values = unpack('vcommand/vchecksum/vsession/vreply', $header);
        return $values;
    }

    protected function readDataResponse($prepareHeader)
    {
        $sizeData = $this->readExactly(4);
        if ($sizeData === false) {
            return false;
        }

        $sizeValues = unpack('Vsize', $sizeData);
        $size = $sizeValues['size'];
        if ($prepareHeader['session'] !== $this->sessionId || $size > 16777215) {
            return false;
        }

        $data = '';
        $received = 0;
        while ($received < $size) {
            $dataHeader = $this->readHeader();
            if ($dataHeader === false || $dataHeader['command'] !== self::CMD_DATA || $dataHeader['session'] !== $this->sessionId) {
                return false;
            }

            $chunkSize = min(self::MAX_CHUNK_SIZE, $size - $received);
            $chunk = $this->readExactly($chunkSize);
            if ($chunk === false) {
                return false;
            }

            $data .= $chunk;
            $received += $chunkSize;
        }

        $trailer = $this->readHeader();
        if ($trailer === false) {
            return false;
        }

        if ($trailer['command'] === self::CMD_ACK_OK && $trailer['session'] === $this->sessionId) {
            $trailer = $this->readHeader();
            if ($trailer === false) {
                return false;
            }
        }

        if ($trailer['command'] !== self::CMD_FREE_DATA || $trailer['session'] !== $this->sessionId) {
            return false;
        }

        return $data;
    }

    protected function checkValid($header)
    {
        if (strlen($header) < self::HEADER_LENGTH) {
            return false;
        }

        $values = unpack('vcommand/vchecksum/vsession/vreply', $header);
        $packet = $header;
        $packet[2] = "\0";
        $packet[3] = "\0";
        $calculated = $this->calcChecksum($packet);
        return $calculated === $values['checksum'];
    }

    protected function writeExactly($packet)
    {
        $written = 0;
        $length = strlen($packet);

        while ($written < $length) {
            $result = @fwrite($this->stream, substr($packet, $written));
            if ($result === false || $result === 0) {
                return false;
            }
            $written += $result;
        }

        return true;
    }

    protected function readExactly($length)
    {
        if (!$this->stream || $length < 0) {
            return false;
        }

        $data = '';
        while (strlen($data) < $length) {
            $chunk = @fread($this->stream, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                $metadata = stream_get_meta_data($this->stream);
                if (!empty($metadata['timed_out']) || feof($this->stream)) {
                    return false;
                }
                return false;
            }
            $data .= $chunk;
        }

        return $data;
    }

    protected function ensureStream()
    {
        if (is_resource($this->stream)) {
            return true;
        }

        $this->stream = $this->connectStream();
        if (!$this->stream) {
            return false;
        }

        return true;
    }

    protected function closeStream()
    {
        if (is_resource($this->stream)) {
            @fclose($this->stream);
        }

        $this->stream = null;
    }

    protected function parseUsersData($userData)
    {
        if (strlen($userData) < 11) {
            return [];
        }

        $sizeValues = unpack('Vsize', substr($userData, 0, 4));
        if ($sizeValues['size'] > strlen($userData) - 4 || $sizeValues['size'] < 7) {
            return [];
        }

        $userData = substr($userData, 11);
        $users = [];

        while (strlen($userData) >= self::USER_RECORD_SIZE) {
            $record = substr($userData, 0, self::USER_RECORD_SIZE);
            $userIdValues = unpack('vuserId', substr($record, 0, 2));
            $privilege = ord($record[2]);
            $password = $this->decodeText(substr($record, 3, 8));
            $name = $this->decodeText(substr($record, 11, 24));
            $pin = $this->decodeText(substr($record, 48, 9));

            if ($name === '' || $pin === '') {
                $userData = substr($userData, self::USER_RECORD_SIZE);
                continue;
            }

            $users[] = [
                'user_id' => $userIdValues['userId'],
                'pin' => $pin,
                'name' => $name,
                'privilege' => $privilege === 14 ? 'Super Admin' : 'User',
            ];

            $userData = substr($userData, self::USER_RECORD_SIZE);
        }

        return $users;
    }

    protected function parseAttendanceData($attendanceData, $threeMonthsAgo)
    {
        if (strlen($attendanceData) < 10) {
            return [];
        }

        $attendanceData = substr($attendanceData, 10);
        $logs = [];

        while (strlen($attendanceData) >= self::ATTENDANCE_RECORD_SIZE) {
            $record = substr($attendanceData, 0, self::ATTENDANCE_RECORD_SIZE);
            $pin = $this->decodeText(substr($record, 2, 9));
            $verified = ord($record[26]);
            $timeValues = unpack('Vtime', substr($record, 27, 4));
            $state = ord($record[31]);
            $timestamp = $this->decodeTime($timeValues['time']);

            if ($pin !== '' && strtotime($timestamp) >= $threeMonthsAgo) {
                $logs[] = [
                    'pin' => $pin,
                    'datetime' => $timestamp,
                    'verified' => (string) $verified,
                    'status' => 'Berhasil',
                    'source' => 'Solution X100C',
                ];
            }

            $attendanceData = substr($attendanceData, self::ATTENDANCE_RECORD_SIZE);
        }

        return $logs;
    }

    protected function decodeText($value)
    {
        $value = str_replace("\0", '', $value);
        $value = trim($value);
        return preg_match('//u', $value) ? $value : '';
    }

    public function connect()
    {
        $this->closeStream();
        $this->sessionId = 0;
        $this->replyId = 0;
        $this->lastConnectionSucceeded = false;

        if (!$this->ensureStream()) {
            return false;
        }

        $packet = $this->buildPacket(self::CMD_CONNECT, 0, 0, 0, '');
        if (!$this->writeExactly($packet)) {
            $this->closeStream();
            return false;
        }

        $header = $this->readHeader();
        if ($header === false || $header['command'] !== self::CMD_ACK_OK) {
            $this->closeStream();
            return false;
        }

        $this->sessionId = $header['session'];
        $this->lastConnectionSucceeded = true;
        return true;
    }

    public function disconnect()
    {
        if (is_resource($this->stream) && $this->sessionId > 0) {
            $this->sendCommand(self::CMD_EXIT, '', self::COMMAND_TYPE_GENERAL);
        }

        $this->closeStream();
        $this->sessionId = 0;
        $this->replyId = 0;
        return true;
    }

    public function getAllUsers()
    {
        if (!$this->connect()) {
            return [];
        }

        $userData = $this->sendCommand(
            self::CMD_USER_TEMP_RRQ,
            chr(self::FCT_USER),
            self::COMMAND_TYPE_DATA
        );

        $users = $userData === false ? [] : $this->parseUsersData($userData);
        $this->disconnect();
        return $users;
    }

    public function downloadLogTigaBulan()
    {
        if (!$this->connect()) {
            return [];
        }

        $attendanceData = $this->sendCommand(
            self::CMD_ATT_LOG_RRQ,
            '',
            self::COMMAND_TYPE_DATA
        );

        $logs = $attendanceData === false
            ? []
            : $this->parseAttendanceData($attendanceData, strtotime('-3 months'));

        $this->disconnect();
        return $logs;
    }

    public function clearLogData()
    {
        if (!$this->connect()) {
            return "Koneksi Gagal";
        }

        $result = $this->sendCommand(self::CMD_CLEAR_ATT_LOG, '', self::COMMAND_TYPE_GENERAL);
        $this->disconnect();

        return $result ? "Sukses" : "Gagal";
    }

    public function hapusUser($id)
    {
        if (!$this->isValidPin($id)) {
            return "Gagal";
        }

        $users = $this->getAllUsers();
        if (!$this->wasLastConnectionSuccessful()) {
            return "Koneksi Gagal";
        }

        $uid = null;
        foreach ($users as $user) {
            if ((string) $user['pin'] === (string) $id) {
                $uid = (int) $user['user_id'];
                break;
            }
        }

        if ($uid === null) {
            return "Gagal";
        }

        if (!$this->connect()) {
            return "Koneksi Gagal";
        }

        $commandString = pack('v', $uid);
        $result = $this->sendCommand(self::CMD_DELETE_USER, $commandString, self::COMMAND_TYPE_GENERAL);
        $this->disconnect();

        return $result ? "Sukses" : "Gagal";
    }

    public function syncTime()
    {
        if (!$this->connect()) {
            return "Koneksi Gagal";
        }

        $now = date('Y-m-d H:i:s');
        $timeVal = $this->encodeTime($now);
        $result = $this->sendCommand(self::CMD_SET_TIME, pack('V', $timeVal), self::COMMAND_TYPE_GENERAL);
        $this->disconnect();

        return $result ? "Waktu berhasil disinkronkan: {$now}" : "Gagal";
    }

    public function restartDevice()
    {
        if (!$this->connect()) {
            return "Koneksi Gagal";
        }

        $result = $this->sendCommand(self::CMD_RESTART, "\0\0", self::COMMAND_TYPE_GENERAL);
        $this->disconnect();

        return $result ? "Sukses" : "Gagal";
    }

    public function uploadNama($id, $nama)
    {
        $name = $this->normalizeName($nama);
        if (!$this->isValidUserId($id) || $name === null) {
            return "Gagal";
        }

        if (!$this->connect()) {
            return "Koneksi Gagal";
        }

        $record = $this->buildUserRecord($id, $name);
        $result = $this->sendCommand(self::CMD_SET_USER, $record, self::COMMAND_TYPE_GENERAL);
        $this->disconnect();

        return $result ? "Sukses" : "Gagal";
    }

    protected function normalizeName($nama)
    {
        if (!is_string($nama) || !preg_match('//u', $nama)) {
            return null;
        }

        $name = trim($nama);
        if ($name === '') {
            return null;
        }

        return mb_strcut($name, 0, 24, 'UTF-8');
    }

    protected function buildUserRecord($id, $name)
    {
        return pack('v', (int) $id)
            . "\0"
            . str_repeat("\0", 8)
            . str_pad($name, 24, "\0")
            . str_repeat("\0", 4)
            . "\0"
            . str_repeat("\0", 2)
            . str_repeat("\0", 6)
            . str_pad((string) $id, 9, "\0")
            . str_repeat("\0", 15);
    }

    protected function isValidUserId($id)
    {
        return filter_var($id, FILTER_VALIDATE_INT) !== false
            && (int) $id > 0
            && (int) $id <= self::USHRT_MAX;
    }

    protected function isValidPin($pin)
    {
        return preg_match('/^\d{1,9}$/', (string) $pin) === 1;
    }

    protected function encodeTime($t)
    {
        $timestamp = strtotime($t);
        if ($timestamp === false) {
            return 0;
        }

        $year = (int) date('Y', $timestamp);
        $month = (int) date('n', $timestamp);
        $day = (int) date('j', $timestamp);
        $hour = (int) date('G', $timestamp);
        $minute = (int) date('i', $timestamp);
        $second = (int) date('s', $timestamp);

        return ((($year % 100) * 12 * 31) + (($month - 1) * 31) + $day - 1) * 86400
            + ($hour * 60 + $minute) * 60
            + $second;
    }

    protected function decodeTime($t)
    {
        $t = (int) $t;
        $second = $t % 60;
        $t = intdiv($t, 60);
        $minute = $t % 60;
        $t = intdiv($t, 60);
        $hour = $t % 24;
        $days = intdiv($t, 24);
        $day = $days % 31 + 1;
        $months = intdiv($days, 31);
        $month = $months % 12 + 1;
        $year = intdiv($months, 12) + self::TIME_EPOCH_YEAR;

        return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
    }
}
