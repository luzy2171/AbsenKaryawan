<?php

namespace Tests\Unit;

use App\Services\SolutionSoapService;
use PHPUnit\Framework\TestCase;

class SolutionSoapServiceTest extends TestCase
{
    public function test_user_rows_are_converted_from_soap_response(): void
    {
        $service = new TestableSolutionSoapService;
        $response = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><GetAllUserInfoResponse><Row><Index>1</Index><PIN>1001</PIN><Name>Alice</Name><Privilege>14</Privilege></Row></GetAllUserInfoResponse></soap:Body></soap:Envelope>';

        self::assertSame([
            [
                'user_id' => 1,
                'pin' => '1001',
                'name' => 'Alice',
                'privilege' => 'Super Admin',
            ],
        ], $service->users($response));
    }

    public function test_attendance_rows_accept_soap_date_time_and_verification(): void
    {
        $service = new TestableSolutionSoapService;
        $response = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><GetAttLogResponse><Row><PIN>1001</PIN><DateTime>2026-09-25 08:30:00</DateTime><Verified>1</Verified><Status>0</Status></Row></GetAttLogResponse></soap:Body></soap:Envelope>';

        self::assertSame([[
            'pin' => '1001',
            'datetime' => '2026-09-25 08:30:00',
            'verified' => '1',
            'status' => '0',
            'source' => 'Solution SOAP SDK',
        ]], $service->logs($response));
    }
}

class TestableSolutionSoapService extends SolutionSoapService
{
    public function users(string $response): array
    {
        $users = [];
        foreach ($this->rowsFromResponse($response) as $row) {
            $pin = $this->rowValue($row, ['PIN', 'UserID', 'UserId']);
            $name = $this->rowValue($row, ['Name', 'UserName']);
            $privilege = $this->rowValue($row, ['Privilege', 'Role']);
            if ($pin === '' || $name === '') {
                continue;
            }

            $users[] = [
                'user_id' => (int) $this->rowValue($row, ['Index', 'SN', 'UserSN']),
                'pin' => $pin,
                'name' => $name,
                'privilege' => in_array($privilege, ['14', 'Super Admin'], true) ? 'Super Admin' : 'User',
            ];
        }

        return $users;
    }

    public function logs(string $response): array
    {
        $logs = [];
        foreach ($this->rowsFromResponse($response) as $row) {
            $pin = $this->rowValue($row, ['PIN', 'UserID', 'UserId']);
            $datetime = $this->rowValue($row, ['DateTime', 'Time', 'Date']);
            if ($pin === '' || $datetime === '') {
                continue;
            }

            $logs[] = [
                'pin' => $pin,
                'datetime' => date('Y-m-d H:i:s', strtotime($datetime)),
                'verified' => $this->rowValue($row, ['Verified', 'VerifyType']),
                'status' => $this->rowValue($row, ['Status', 'State']),
                'source' => 'Solution SOAP SDK',
            ];
        }

        return $logs;
    }
}
