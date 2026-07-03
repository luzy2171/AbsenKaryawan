<?php

namespace App\Services;

class AbsensiService
{
    protected $ip;
    protected $key;

    public function __construct()
    {
        // Konfigurasi IP Mesin Absensi Fisik Anda
        $this->ip = '10.10.10.237';
        $this->key = '0';
    }

    /**
     * Helper internal: Memotong data XML (pengganti parse.php)
     */
    private function parseData($data, $p1, $p2)
    {
        $data = " " . $data;
        $ini = strpos($data, $p1);
        if ($ini == 0) return "";
        $ini += strlen($p1);
        $len = strpos($data, $p2, $ini) - $ini;
        return substr($data, $ini, $len);
    }

    /**
     * Helper internal: Eksekusi HTTP POST request via Socket fsockopen
     */
    private function executeSocket($soapRequest)
    {
        $connect = @fsockopen($this->ip, 80, $errno, $errstr, 5);
        if (!$connect) {
            return false;
        }

        $newLine = "\r\n";
        fwrite($connect, "POST /iWsService HTTP/1.0" . $newLine);
        fwrite($connect, "Content-Type: text/xml" . $newLine);
        fwrite($connect, "Content-Length: " . strlen($soapRequest) . $newLine . $newLine);
        fwrite($connect, $soapRequest);

        $buffer = "";
        while (!feof($connect)) {
            $buffer .= fgets($connect, 1024);
        }
        fclose($connect);
        return $buffer;
    }

    /**
     * 1. GET ALL USER INFO (Dari SDK Get User)
     */
    public function getAllUsers()
    {
        $soap = '<?xml version="1.0"?><GetAllUserInfo><ArgComKey>'.$this->key.'</ArgComKey></GetAllUserInfo>';
        $response = $this->executeSocket($soap);

        if (!$response) return [];

        $buffer = $this->parseData($response, "<GetAllUserInfoResponse>", "</GetAllUserInfoResponse>");
        $rows = explode("\r\n", $buffer);
        $users = [];

        foreach ($rows as $row) {
            $data = $this->parseData($row, "<Row>", "</Row>");
            if (trim($data) == "") continue;

            $pin  = $this->parseData($data, "<PIN>", "</PIN>");
            $name = $this->parseData($data, "<Name>", "</Name>");

            $users[] = ['pin' => $pin, 'name' => $name];
        }
        return $users;
    }

    /**
     * 2. DOWNLOAD LOG ABSENSI + GABUNG NAMA (Dari SDK Get Log)
     */
    public function downloadLogDenganNama()
    {
        // Ambil pemetaan nama user terlebih dahulu
        $users = $this->getAllUsers();
        $userList = [];
        foreach ($users as $u) {
            $userList[$u['pin']] = $u['name'];
        }

        $soap = '<?xml version="1.0"?><GetAttLog><ArgComKey>'.$this->key.'</ArgComKey><Arg><PIN>All</PIN></Arg></GetAttLog>';
        $response = $this->executeSocket($soap);

        if (!$response) return [];

        $buffer = $this->parseData($response, "<GetAttLogResponse>", "</GetAttLogResponse>");
        $rows = explode("\r\n", $buffer);
        $logs = [];

        foreach ($rows as $row) {
            $data = $this->parseData($row, "<Row>", "</Row>");
            if (trim($data) == "") continue;

            $pin = $this->parseData($data, "<PIN>", "</PIN>");

            $logs[] = [
                'pin'       => $pin,
                'nama'      => $userList[$pin] ?? '-',
                'datetime'  => $this->parseData($data, "<DateTime>", "</DateTime>"),
                'verified'  => $this->parseData($data, "<Verified>", "</Verified>"),
                'status'    => $this->parseData($data, "<Status>", "</Status>"),
            ];
        }
        return $logs;
    }

    /**
     * 3. DOWNLOAD LOG MENTAH (Untuk AbsensiController)
     */
    public function downloadLog()
    {
        $soap = '<?xml version="1.0"?><GetAttLog><ArgComKey>'.$this->key.'</ArgComKey><Arg><PIN>All</PIN></Arg></GetAttLog>';
        $response = $this->executeSocket($soap);

        if (!$response) return [];

        $buffer = $this->parseData($response, "<GetAttLogResponse>", "</GetAttLogResponse>");
        $rows = explode("\r\n", $buffer);
        $logs = [];

        foreach ($rows as $row) {
            $data = $this->parseData($row, "<Row>", "</Row>");
            if (trim($data) == "") continue;

            $logs[] = [
                'pin'      => $this->parseData($data, "<PIN>", "</PIN>"),
                'datetime' => $this->parseData($data, "<DateTime>", "</DateTime>"),
                'verified' => $this->parseData($data, "<Verified>", "</Verified>"),
                'status'   => $this->parseData($data, "<Status>", "</Status>"),
            ];
        }
        return $logs;
    }

    /**
     * 3b. SINKRONISASI LOG ABSENSI 3 BULAN TERAKHIR (Fitur Baru Terfilter)
     */
    public function downloadLogTigaBulan()
    {
        $soap = '<?xml version="1.0"?><GetAttLog><ArgComKey>'.$this->key.'</ArgComKey><Arg><PIN>All</PIN></Arg></GetAttLog>';
        $response = $this->executeSocket($soap);

        if (!$response) return [];

        $buffer = $this->parseData($response, "<GetAttLogResponse>", "</GetAttLogResponse>");
        $rows = explode("\r\n", $buffer);

        $logsFiltered = [];

        // Menghitung ambang batas tanggal 3 bulan ke belakang dari hari ini
        $batasTanggal = date('Y-m-d H:i:s', strtotime('-3 months'));

        foreach ($rows as $row) {
            $data = $this->parseData($row, "<Row>", "</Row>");
            if (trim($data) == "") continue;

            $dateTime = $this->parseData($data, "<DateTime>", "</DateTime>");

            // Validasi filter: Rekaman hanya masuk jika tanggalnya lebih baru dari 3 bulan lalu
            if ($dateTime >= $batasTanggal) {
                $logsFiltered[] = [
                    'pin'      => $this->parseData($data, "<PIN>", "</PIN>"),
                    'datetime' => $dateTime,
                    'verified' => $this->parseData($data, "<Verified>", "</Verified>"),
                    'status'   => $this->parseData($data, "<Status>", "</Status>"),
                ];
            }
        }
        return $logsFiltered;
    }

    /**
     * 4. DOWNLOAD SIDIK JARI (Dari SDK Download Sidik Jari)
     */
    public function getFingerprintTemplate($id, $fn)
    {
        $soap = '<?xml version="1.0" encoding="UTF-8"?>
        <GetUserTemplate>
            <ArgComKey xsi:type="xsd:integer">'.$this->key.'</ArgComKey>
            <Arg>
                <PIN xsi:type="xsd:integer">'.$id.'</PIN>
                <FingerID xsi:type="xsd:integer">'.$fn.'</FingerID>
            </Arg>
        </GetUserTemplate>';

        $response = $this->executeSocket($soap);
        if (!$response) return [];

        $buffer = $this->parseData($response, "<GetUserTemplateResponse>", "</GetUserTemplateResponse>");
        $rows = explode("\r\n", $buffer);
        $templates = [];

        foreach ($rows as $row) {
            $data = $this->parseData($row, "<Row>", "</Row>");
            if (trim($data) == "") continue;

            $templates[] = [
                'pin'       => $this->parseData($data, "<PIN>", "</PIN>"),
                'finger_id' => $this->parseData($data, "<FingerID>", "</FingerID>"),
                'size'      => $this->parseData($data, "<Size>", "</Size>"),
                'valid'     => $this->parseData($data, "<Valid>", "</Valid>"),
                'template'  => $this->parseData($data, "<Template>", "</Template>"),
            ];
        }
        return $templates;
    }

    /**
     * 5. CLEAR LOG DATA (Dari SDK Clear Data)
     */
    public function clearLogData()
    {
        $soap = "<ClearData><ArgComKey xsi:type=\"xsd:integer\">".$this->key."</ArgComKey><Arg><Value xsi:type=\"xsd:integer\">3</Value></Arg></ClearData>";
        $response = $this->executeSocket($soap);
        if (!$response) return "Koneksi Gagal";

        return $this->parseData($response, "<Information>", "</Information>");
    }

    /**
     * 6. HAPUS USER DARI MESIN (Dari SDK Hapus User)
     */
    public function hapusUser($id)
    {
        $soap = "<DeleteUser><ArgComKey xsi:type=\"xsd:integer\">".$this->key."</ArgComKey><Arg><PIN xsi:type=\"xsd:integer\">".$id."</PIN></Arg></DeleteUser>";
        $response = $this->executeSocket($soap);

        if (!$response) return "Koneksi Gagal";
        return $this->parseData($response, "<Information>", "</Information>");
    }

    /**
     * 7. SYNCHRONIZE TIME (Dari SDK Syn Time dengan Tag <SetDate>)
     */
    public function syncTime()
    {
        $currentDate = date('Y-m-d');
        $currentTime = date('H:i:s');

        $soap = "<SetDate>
                    <ArgComKey xsi:type=\"xsd:integer\">".$this->key."</ArgComKey>
                    <Arg>
                        <Date xsi:type=\"xsd:string\">".$currentDate."</Date>
                        <Time xsi:type=\"xsd:string\">".$currentTime."</Time>
                    </Arg>
                 </SetDate>";

        $response = $this->executeSocket($soap);
        if (!$response) return "Koneksi Gagal";

        return $this->parseData($response, "<Information>", "</Information>");
    }

    /**
     * 8. RESTART DEVICE (Dari SDK Restart)
     */
    public function restartDevice()
    {
        $soap = "<Restart><ArgComKey xsi:type=\"xsd:integer\">".$this->key."</ArgComKey></Restart>";
        $response = $this->executeSocket($soap);

        if (!$response) return "Koneksi Gagal";
        return $this->parseData($response, "<Information>", "</Information>");
    }

    /**
     * 9. UPLOAD NAMA KARYAWAN
     */
    public function uploadNama($id, $nama)
    {
        $soap = "<SetUserInfo><ArgComKey Xsi:type=\"xsd:integer\">".$this->key."</ArgComKey><Arg><PIN>".$id."</PIN><Name>".$nama."</Name></Arg></SetUserInfo>";
        $response = $this->executeSocket($soap);
        return $this->parseData($response, "<Information>", "</Information>");
    }

    /**
     * 10. UPLOAD SIDIK JARI KE MESIN
     */
    public function uploadSidikJari($id, $fn, $template)
    {
        $size = strlen($template);
        $soap = '<?xml version="1.0" encoding="UTF-8"?>
        <SetUserTemplate>
            <ArgComKey xsi:type="xsd:integer">'.$this->key.'</ArgComKey>
            <Arg>
                <PIN xsi:type="xsd:integer">'.$id.'</PIN>
                <FingerID xsi:type="xsd:integer">'.$fn.'</FingerID>
                <Size>'.$size.'</Size>
                <Valid>1</Valid>
                <Template>'.$template.'</Template>
            </Arg>
        </SetUserTemplate>';

        $response = $this->executeSocket($soap);

        // Refresh DB internal mesin agar perubahan langsung aktif
        $refreshSoap = 'xml version="1.0"?><RefreshDB><ArgComKey>'.$this->key.'</ArgComKey></RefreshDB>';
        $this->executeSocket($refreshSoap);

        if (!$response) return "Koneksi Gagal";
        return $this->parseData($response, "<Information>", "</Information>");
    }

    /**
     * 11. HAPUS TEMPLATE SIDIK JARI DARI MESIN
     */
    public function deleteSidikJari($id, $fn)
    {
        $soap = '<?xml version="1.0"?>
        <DeleteTemplate>
            <ArgComKey xsi:type="xsd:integer">'.$this->key.'</ArgComKey>
            <Arg>
                <PIN xsi:type="xsd:integer">'.$id.'</PIN>
                <FingerID xsi:type="xsd:integer">'.$fn.'</FingerID>
            </Arg>
        </DeleteTemplate>';

        $response = $this->executeSocket($soap);
        if (!$response) return "Koneksi Gagal";

        return $this->parseData($response, "<Information>", "</Information>");
    }
}
