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
                'name' => $name,
                'privilege' => in_array($privilege, ['14', 'Super Admin'], true) ? 'Super Admin' : 'User',
            ];
        }

        return $users;
    }

    public function downloadLogTigaBulan(): array
    {
        try {
            $response = $this->request(
                'GetAttLog',
                $this->comKeyBody() . '<Arg><PIN xsi:type="xsd:integer">All</PIN></Arg>'
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
        if ($this->requestSuccessful('ClearData', $this->comKeyBody() . '<Arg><Value xsi:type="xsd:integer">3</Value></Arg>')) {
            return 'Sukses';
        }

        return 'Gagal';
    }

    public function hapusUser($id): string
    {
        if (preg_match('/^\d{1,9}$/', (string) $id) !== 1) {
            return 'Gagal';
        }

        $body = $this->comKeyBody() . '<Arg><PIN xsi:type="xsd:integer">' . $this->escape($id) . '</PIN></Arg>';
        return $this->requestSuccessful('DeleteUser', $body) ? 'Sukses' : 'Gagal';
    }

    public function syncTime(): string
    {
        $body = $this->comKeyBody()
            . '<Arg><Date xsi:type="xsd:string">' . $this->escape(date('Y-m-d')) . '</Date>'
            . '<Time xsi:type="xsd:string">' . $this->escape(date('H:i:s')) . '</Time></Arg>';

        if (!$this->requestSuccessful('SetDate', $body)) {
            return 'Gagal';
        }

        return 'Waktu berhasil disinkronkan: ' . date('Y-m-d H:i:s');
    }

    public function restartDevice(): string
    {
        return $this->requestSuccessful('Restart', $this->comKeyBody()) ? 'Sukses' : 'Gagal';
    }

    public function uploadNama($id, $nama): string
    {
        if (filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id < 1 || (int) $id > 65535 || !is_string($nama)) {
            return 'Gagal';
        }

        $name = trim($nama);
        if ($name === '' || !preg_match('//u', $name)) {
            return 'Gagal';
        }

        $body = $this->comKeyBody()
            . '<Arg><PIN xsi:type="xsd:integer">' . $this->escape((string) $id) . '</PIN>'
            . '<Name>' . $this->escape($name) . '</Name></Arg>';

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

        $address = 'tcp://' . $this->ip . ':' . $this->port;
        $errno = 0;
        $error = '';
        $stream = @stream_socket_client($address, $errno, $error, 5, STREAM_CLIENT_CONNECT);
        if (!$stream) {
            throw new RuntimeException('SOAP connection failed: ' . $error);
        }

        try {
            stream_set_timeout($stream, 10);
            $xml = $this->envelope($operation, $body);
            $request = 'POST /iWsService HTTP/1.0' . "\r\n"
                . 'Host: ' . $this->ip . "\r\n"
                . 'Content-Type: text/xml; charset=utf-8' . "\r\n"
                . 'SOAPAction: "' . $operation . '"' . "\r\n"
                . 'Content-Length: ' . strlen($xml) . "\r\n"
                . 'Connection: close' . "\r\n\r\n"
                . $xml;

            $written = 0;
            while ($written < strlen($request)) {
                $result = @fwrite($stream, substr($request, $written));
                if ($result === false || $result === 0) {
                    throw new RuntimeException('SOAP connection write failed.');
                }
                $written += $result;
            }

            $response = '';
            while (!feof($stream)) {
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
            throw new RuntimeException('SOAP HTTP status ' . $status . '.');
        }

        $parts = preg_split("/\r\n\r\n/", $response, 2);
        if (!isset($parts[1]) || $parts[1] === '') {
            throw new RuntimeException('Empty SOAP response.');
        }

        $this->lastConnectionSucceeded = true;
        return $parts[1];
    }

    protected function rowsFromResponse(string $response): array
    {
        $document = new DOMDocument();
        if (!@$document->loadXML($response)) {
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
            if (!$node instanceof DOMElement) {
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
        $document = new DOMDocument();
        if (!@$document->loadXML($response)) {
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
        return '<ArgComKey xsi:type="xsd:integer">' . $this->escape($this->commKey) . '</ArgComKey>';
    }

    protected function envelope(string $operation, string $body): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<soap:Body><' . $operation . '>' . $body . '</' . $operation . '></soap:Body>'
            . '</soap:Envelope>';
    }

    protected function escape($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
