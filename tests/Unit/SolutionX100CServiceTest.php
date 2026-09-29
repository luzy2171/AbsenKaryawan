<?php

namespace Tests\Unit;

use App\Services\SolutionX100CService;
use PHPUnit\Framework\TestCase;

class SolutionX100CServiceTest extends TestCase
{
    public function test_build_packet_uses_little_endian_standard_connect_fixture(): void
    {
        $service = new TestableSolutionX100CService();

        self::assertSame('e80317fc00000100', bin2hex($service->packet(1000, 0, 0, 0)));
    }

    public function test_build_packet_matches_user_request_fixture(): void
    {
        $service = new TestableSolutionX100CService();

        self::assertSame('0900e7ff0900020005', bin2hex($service->packet(9, 0, 9, 1, "\x05")));
    }

    public function test_response_header_requires_valid_checksum(): void
    {
        $service = new TestableSolutionX100CService();
        $valid = hex2bin('d00725f809000100');
        $invalid = hex2bin('d00726f809000100');

        self::assertTrue($service->valid($valid));
        self::assertFalse($service->valid($invalid));
    }

    public function test_user_record_fixture_is_parsed_with_little_endian_uid(): void
    {
        $service = new TestableSolutionX100CService();
        $record = pack('v', 258)
            . "\x0e"
            . str_repeat("\0", 8)
            . str_pad('Alice', 24, "\0")
            . str_repeat("\0", 4)
            . "\0"
            . str_repeat("\0", 2)
            . str_repeat("\0", 6)
            . str_pad('1001', 9, "\0")
            . str_repeat("\0", 15);
        $payload = pack('V', 7 + strlen($record)) . str_repeat("\0", 7) . $record;

        self::assertSame([
            [
                'user_id' => 258,
                'pin' => '1001',
                'name' => 'Alice',
                'privilege' => 'Super Admin',
            ],
        ], $service->users($payload));
    }

    public function test_attendance_record_fixture_uses_little_endian_time(): void
    {
        $service = new TestableSolutionX100CService();
        $encodedTime = $service->time('2000-02-03 04:05:06');
        $record = pack('v', 42)
            . str_pad('1002', 9, "\0")
            . str_repeat("\0", 15)
            . "\x01"
            . pack('V', $encodedTime)
            . "\x01"
            . str_repeat("\0", 8);
        $payload = str_repeat("\0", 10) . $record;

        self::assertSame([[
            'pin' => '1002',
            'datetime' => '2000-02-03 04:05:06',
            'verified' => '1',
            'status' => 'Berhasil',
            'source' => 'Solution X100C',
        ]], $service->attendance($payload, 0));
    }

    public function test_time_encoding_uses_year_2000_epoch(): void
    {
        $service = new TestableSolutionX100CService();

        self::assertSame(0, $service->time('2000-01-01 00:00:00'));
        self::assertSame('2024-12-31 23:59:59', $service->date($service->time('2024-12-31 23:59:59')));
    }

    public function test_user_name_is_validated_and_truncated_by_bytes(): void
    {
        $service = new TestableSolutionX100CService();
        $name = $service->name('Nama Karyawan yang Sangat Panjang');
        $multibyte = $service->name('Éléonore Jeonghyeon Kim');
        $record = $service->record(123, $name);

        self::assertNotNull($name);
        self::assertLessThanOrEqual(24, strlen($name));
        self::assertNotNull($multibyte);
        self::assertLessThanOrEqual(24, strlen($multibyte));
        self::assertTrue((bool) preg_match('//u', $multibyte));
        self::assertNull($service->name("\xB1\x31"));
        self::assertSame(72, strlen($record));
    }
}

class TestableSolutionX100CService extends SolutionX100CService
{
    public function packet($command, $checksum, $sessionId, $replyId, $payload = ''): string
    {
        return $this->buildPacket($command, $checksum, $sessionId, $replyId, $payload);
    }

    public function valid(string $header): bool
    {
        return $this->checkValid($header);
    }

    public function users(string $payload): array
    {
        return $this->parseUsersData($payload);
    }

    public function attendance(string $payload, int $threeMonthsAgo): array
    {
        return $this->parseAttendanceData($payload, $threeMonthsAgo);
    }

    public function time(string $value): int
    {
        return $this->encodeTime($value);
    }

    public function date(int $value): string
    {
        return $this->decodeTime($value);
    }

    public function name(string $value): ?string
    {
        return $this->normalizeName($value);
    }

    public function record(int $id, string $name): string
    {
        return $this->buildUserRecord($id, $name);
    }
}
