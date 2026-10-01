<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use Throwable;

class SolutionSoapService
{
    protected $ip;

    protected $port = 80;

    protected $commKey = '0';

    protected $lastConnectionSucceeded = false;

    public function setConnection($ip, $port = 80, $commKey = '0')
    {
        $this->ip = $ip;
        $this->port = (int) $port;
        $this->commKey = preg_match('/^\d{1,10}$/', (string) $commKey) === 1 ? (string) $commKey : '0';
        $this->lastConnectionSucceeded = false;

        return $this;
    }

    public function wasLastConnectionSuccessful(): bool
    {
        return $this->lastConnectionSucceeded;
    }

    public function ping(): bool
    {
        return $this->requestSuccessful('GetAllUserInfo', $this->comKeyBody());
    }

    public function getAllUsers(): array
    {
        try {
            $response = $this->request('GetAllUserInfo', $this->comKeyBody());
            $this->lastConnectionSucceeded = true;
        } catch (Throwable $exception) {
            $this->lastConnectionSucceeded = false;

            return [];
        }

        $users = [];
        foreach ($this->rowsFromResponse($response) as $row) {
            $pin = $this->rowValue($row, ['PIN', 'UserID', 'UserId']);
            $name = $this->rowValue($row, ['Name', 'UserName']);
            if ($pin === '' || $name === '') {
                continue;
            }

            $privilege = $this->rowValue($row, ['Privilege', 'Role']);
            $users[] = [
                'user_id' => (int) $this->rowValue($row, ['Index', 'SN', 'UserSN']),
                'pin' => $pin,
                // PIN2 adalah user id yang muncul di log absensi (mesin
                // mengalokasikan <PIN> sendiri sebagai record id internal).
                'pin2' => $this->rowValue($row, ['PIN2']),
                'name' => $name,
                'privilege' => in_array($privilege, ['14', 'Super Admin'], true) ? 'Super Admin' : 'User',
            ];
        }

        return $users;
    }

    /**
     * Ambil seluruh template sidik jari milik satu user dari mesin.
     *
     * @return array<int, array{finger_id:int, template:string, size:int}>
     */
    public function getUserTemplates(string $pin): array
    {
        $pin = trim($pin);
        if (preg_match('/^\d{1,9}$/', $pin) !== 1) {
            return [];
        }

        $hasil = [];

        for ($fingerId = 0; $fingerId <= 9; $fingerId++) {
            $template = $this->getUserTemplate($pin, $fingerId);
            if ($template === '') {
                continue;
            }

            $hasil[] = [
                'finger_id' => $fingerId,
                'template' => $template,
                'size' => strlen($template),
            ];
        }

        return $hasil;
    }

    /**
     * Ambil satu template sidik jari. String kosong = tidak terdaftar / gagal.
     */
    public function getUserTemplate(string $pin, int $fingerId): string
    {
        $pin = trim($pin);
        if (preg_match('/^\d{1,9}$/', $pin) !== 1 || $fingerId < 0 || $fingerId > 9) {
            return '';
        }

        try {
            $response = $this->request('GetUserTemplate', $this->comKeyBody()
                .'<Arg><PIN xsi:type="xsd:integer">'.$this->escape($pin).'</PIN>'
                .'<FingerID xsi:type="xsd:integer">'.$fingerId.'</FingerID></Arg>');
            $this->lastConnectionSucceeded = true;
        } catch (Throwable $exception) {
            $this->lastConnectionSucceeded = false;

            return '';
        }

        foreach ($this->rowsFromResponse($response) as $row) {
            $template = $this->rowValue($row, ['Template']);
            if ($template !== '') {
                return $template;
            }
        }

        return '';
    }

    /**
     * Kirim template sidik jari ke mesin, lalu RefreshDB agar mesin membaca ulang.
     */
    public function setUserTemplate(string $pin, int $fingerId, string $template): string
    {
        $pin = trim($pin);
        if (preg_match('/^\d{1,9}$/', $pin) !== 1) {
            return 'PIN tidak valid';
        }
        if ($fingerId < 0 || $fingerId > 9) {
            return 'Finger ID tidak valid';
        }
        if ($template === '' || strlen($template) > 8192) {
            return 'Template kosong atau terlalu besar';
        }

        try {
            $response = $this->request('SetUserTemplate', $this->comKeyBody()
                .'<Arg><PIN xsi:type="xsd:integer">'.$this->escape($pin).'</PIN>'
                .'<FingerID xsi:type="xsd:integer">'.$fingerId.'</FingerID>'
                .'<Size>'.strlen($template).'</Size><Valid>1</Valid>'
                .'<Template>'.$this->escape($template).'</Template></Arg>');

            $this->lastConnectionSucceeded = true;

            if ($this->responseInformation($response) !== '') {
                $this->request('RefreshDB', $this->comKeyBody());
            }

            return 'Sukses';
        } catch (Throwable $exception) {
            $this->lastConnectionSucceeded = false;

            return 'Koneksi gagal: '.$exception->getMessage();
        }
    }

    /**
     * Kirim nama user ke mesin (dipakai juga saat memulihkan user dari backup).
     */
    public function setUserInfo(string $pin, string $name): string
    {
        $pin = trim($pin);
        if (preg_match('/^\d{1,9}$/', $pin) !== 1) {
            return 'PIN tidak valid';
        }

        $name = trim((string) $name);
        if ($name === '') {
            return 'Nama kosong';
        }

        try {
            $this->request('SetUserInfo', $this->comKeyBody()
                .'<Arg><PIN xsi:type="xsd:integer">'.$this->escape($pin).'</PIN>'
                .'<Name>'.$this->escape($name).'</Name></Arg>');
            $this->lastConnectionSucceeded = true;

            return 'Sukses';
        } catch (Throwable $exception) {
            $this->lastConnectionSucceeded = false;

            return 'Koneksi gagal: '.$exception->getMessage();
        }
    }

    /**
     * Minta mesin membaca ulang database internalnya (setelah template ditulis).
     */
    public function refreshDatabase(): bool
    {
        try {
            $this->request('RefreshDB', $this->comKeyBody());
            $this->lastConnectionSucceeded = true;

            return true;
        } catch (Throwable $exception) {
            $this->lastConnectionSucceeded = false;

            return false;
        }
    }

    /**
     * Hapus template sidik jari dari mesin.
     */
    public function deleteTemplate(string $pin): string
    {
        $pin = trim($pin);
        if (preg_match('/^\d{1,9}$/', $pin) !== 1) {
            return 'PIN tidak valid';
        }

        try {
            $this->request('DeleteTemplate', $this->comKeyBody()
                .'<Arg><PIN xsi:type="xsd:integer">'.$this->escape($pin).'</PIN></Arg>');
            $this->lastConnectionSucceeded = true;
            $this->request('RefreshDB', $this->comKeyBody());

            return 'Sukses';
        } catch (Throwable $exception) {
            $this->lastConnectionSucceeded = false;

            return 'Koneksi gagal: '.$exception->getMessage();
        }
    }

    public function downloadLogTigaBulan(): array
    {
        try {
            $response = $this->request(
                'GetAttLog',
                $this->comKeyBody().'<Arg><PIN xsi:type="xsd:integer">All</PIN></Arg>'
            );
            $this->lastConnectionSucceeded = true;
        } catch (Throwable $exception) {
            $this->lastConnectionSucceeded = false;

            return [];
        }

        $threeMonthsAgo = strtotime('-3 months');
        $logs = [];
        foreach ($this->rowsFromResponse($response) as $row) {
            $pin = $this->rowValue($row, ['PIN', 'UserID', 'UserId']);
            $datetime = $this->rowValue($row, ['DateTime', 'Time', 'Date']);
            $timestamp = strtotime($datetime);
            if ($pin === '' || $timestamp === false || $timestamp < $threeMonthsAgo) {
                continue;
            }

            $logs[] = [
                'pin' => $pin,
                'datetime' => date('Y-m-d H:i:s', $timestamp),
                'verified' => $this->rowValue($row, ['Verified', 'VerifyType']),
                'status' => $this->rowValue($row, ['Status', 'State']),
                'source' => 'Solution SOAP SDK',
            ];
        }

        return $logs;
    }

    public function clearLogData(): string
    {
        if ($this->requestSuccessful('ClearData', $this->comKeyBody().'<Arg><Value xsi:type="xsd:integer">3</Value></Arg>')) {
            return 'Sukses';
        }

        return 'Gagal';
    }

    public function hapusUser($id): string
    {
        if (preg_match('/^\d{1,9}$/', (string) $id) !== 1) {
            return 'Gagal';
        }

        $body = $this->comKeyBody().'<Arg><PIN xsi:type="xsd:integer">'.$this->escape($id).'</PIN></Arg>';

        return $this->requestSuccessful('DeleteUser', $body) ? 'Sukses' : 'Gagal';
    }

    public function syncTime(): string
    {
        $body = $this->comKeyBody()
            .'<Arg><Date xsi:type="xsd:string">'.$this->escape(date('Y-m-d')).'</Date>'
            .'<Time xsi:type="xsd:string">'.$this->escape(date('H:i:s')).'</Time></Arg>';

        if (! $this->requestSuccessful('SetDate', $body)) {
            return 'Gagal';
        }

        return 'Waktu berhasil disinkronkan: '.date('Y-m-d H:i:s');
    }

    public function restartDevice(): string
    {
        return $this->requestSuccessful('Restart', $this->comKeyBody()) ? 'Sukses' : 'Gagal';
    }

    public function uploadNama($id, $nama): string
    {
        if (filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id < 1 || (int) $id > 65535 || ! is_string($nama)) {
            return 'Gagal';
        }

        $name = trim($nama);
        if ($name === '' || ! preg_match('//u', $name)) {
            return 'Gagal';
        }

        $body = $this->comKeyBody()
            .'<Arg><PIN xsi:type="xsd:integer">'.$this->escape((string) $id).'</PIN>'
            .'<Name>'.$this->escape($name).'</Name></Arg>';

        return $this->requestSuccessful('SetUserInfo', $body) ? 'Sukses' : 'Gagal';
    }

    protected function requestSuccessful(string $operation, string $body): bool
    {
        try {
            $response = $this->request($operation, $body);
            $information = $this->responseInformation($response);
            if ($information !== '' && preg_match('/\b(error|failed|invalid|wrong|unauthorized|denied)\b/i', $information) === 1) {
                throw new RuntimeException('SOAP device returned an error.');
            }

            return true;
        } catch (Throwable $exception) {
            $this->lastConnectionSucceeded = false;

            return false;
        }
    }

    protected function request(string $operation, string $body): string
    {
        if (filter_var($this->ip, FILTER_VALIDATE_IP) === false || $this->port < 1 || $this->port > 65535) {
            throw new RuntimeException('Invalid SOAP machine connection.');
        }

        $address = 'tcp://'.$this->ip.':'.$this->port;
        $errno = 0;
        $error = '';
        $stream = @stream_socket_client($address, $errno, $error, 5, STREAM_CLIENT_CONNECT);
        if (! $stream) {
            throw new RuntimeException('SOAP connection failed: '.$error);
        }

        try {
            stream_set_timeout($stream, 10);
            $xml = $this->envelope($operation, $body);
            $request = 'POST /iWsService HTTP/1.0'."\r\n"
                .'Host: '.$this->ip."\r\n"
                .'Content-Type: text/xml; charset=utf-8'."\r\n"
                .'SOAPAction: "'.$operation.'"'."\r\n"
                .'Content-Length: '.strlen($xml)."\r\n"
                .'Connection: close'."\r\n\r\n"
                .$xml;

            $written = 0;
            while ($written < strlen($request)) {
                $result = @fwrite($stream, substr($request, $written));
                if ($result === false || $result === 0) {
                    throw new RuntimeException('SOAP connection write failed.');
                }
                $written += $result;
            }

            $response = '';
            while (! feof($stream)) {
                $chunk = fread($stream, 8192);
                if ($chunk === false) {
                    throw new RuntimeException('SOAP connection read failed.');
                }
                $response .= $chunk;
            }
        } finally {
            fclose($stream);
        }

        if (preg_match('/^HTTP\/\d\.\d\s+(\d{3})/', $response, $matches) !== 1) {
            throw new RuntimeException('Invalid SOAP HTTP response.');
        }

        $status = (int) $matches[1];
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('SOAP HTTP status '.$status.'.');
        }

        $parts = preg_split("/\r\n\r\n/", $response, 2);
        if (! isset($parts[1]) || $parts[1] === '') {
            throw new RuntimeException('Empty SOAP response.');
        }

        $this->lastConnectionSucceeded = true;

        return $parts[1];
    }

    protected function rowsFromResponse(string $response): array
    {
        $document = new DOMDocument;
        if (! @$document->loadXML($response)) {
            return [];
        }

        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//*[local-name()="Row" or local-name()="User"]');
        if ($nodes === false) {
            return [];
        }

        $rows = [];
        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $rows[] = $node;
            }
        }

        return $rows;
    }

    protected function rowValue(DOMElement $row, array $names): string
    {
        foreach ($row->childNodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            if (in_array(strtolower($node->localName), array_map('strtolower', $names), true)) {
                return trim($node->textContent);
            }
        }

        return '';
    }

    protected function responseInformation(string $response): string
    {
        $document = new DOMDocument;
        if (! @$document->loadXML($response)) {
            return '';
        }

        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//*[local-name()="Information"]');
        if ($nodes === false || $nodes->length === 0) {
            return '';
        }

        return trim($nodes->item(0)->textContent);
    }

    protected function comKeyBody(): string
    {
        return '<ArgComKey xsi:type="xsd:integer">'.$this->escape($this->commKey).'</ArgComKey>';
    }

    protected function envelope(string $operation, string $body): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<soap:Body><'.$operation.'>'.$body.'</'.$operation.'></soap:Body>'
            .'</soap:Envelope>';
    }

    protected function escape($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
