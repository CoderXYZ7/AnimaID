<?php

namespace AnimaID\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use AnimaID\Services\AttendanceService;
use AnimaID\Repositories\AttendanceRepository;
use AnimaID\Config\ConfigManager;
use PDO;
use PDOStatement;

class AttendanceServiceTest extends TestCase
{
    private $attendanceRepository;
    private $config;
    private $attendanceService;

    protected function setUp(): void
    {
        $this->attendanceRepository = $this->createMock(AttendanceRepository::class);
        $this->config               = $this->createMock(ConfigManager::class);

        $this->attendanceService = new AttendanceService(
            $this->attendanceRepository,
            $this->config
        );
    }

    public function testGetByEvent(): void
    {
        $mockRecords = [
            ['id' => 1, 'event_id' => 10, 'child_name' => 'Alice', 'check_in_time' => '2026-03-15 09:00:00'],
            ['id' => 2, 'event_id' => 10, 'child_name' => 'Bob',   'check_in_time' => '2026-03-15 09:05:00']
        ];

        $this->attendanceRepository->expects($this->once())
            ->method('findByEvent')
            ->with(10)
            ->willReturn($mockRecords);

        $result = $this->attendanceService->getByEvent(10);

        $this->assertCount(2, $result);
        $this->assertEquals('Alice', $result[0]['child_name']);
    }

    public function testCheckIn(): void
    {
        // Mock the PDOStatement returned by PDO::prepare inside resolveParticipantId
        $pdoStatement = $this->createMock(PDOStatement::class);
        $pdoStatement->method('execute')->willReturn(true);
        $pdoStatement->method('fetch')->willReturn(['id' => 99]);

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('prepare')->willReturn($pdoStatement);

        $this->attendanceRepository->method('getPdo')->willReturn($mockPdo);
        $this->attendanceRepository->expects($this->once())
            ->method('checkIn')
            ->with($this->callback(function (array $data) {
                return $data['participant_id'] === 99
                    && $data['event_id'] === 10
                    && $data['status'] === 'present';
            }));

        $this->attendanceService->checkIn(['child_id' => 5, 'event_id' => 10], 1);
    }

    public function testCheckInMissingIds(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Child ID and Event ID are required');

        $this->attendanceService->checkIn(['child_id' => 0, 'event_id' => 0], 1);
    }

    public function testCheckInChildNotRegistered(): void
    {
        $pdoStatement = $this->createMock(PDOStatement::class);
        $pdoStatement->method('execute')->willReturn(true);
        $pdoStatement->method('fetch')->willReturn(false); // no participant row

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('prepare')->willReturn($pdoStatement);

        $this->attendanceRepository->method('getPdo')->willReturn($mockPdo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Child is not registered for this event');

        $this->attendanceService->checkIn(['child_id' => 5, 'event_id' => 10], 1);
    }

    public function testCheckOut(): void
    {
        $existingRecord = [
            'id'    => 1,
            'notes' => 'existing note'
        ];

        $this->attendanceRepository->method('findById')->with(1)->willReturn($existingRecord);
        $this->attendanceRepository->expects($this->once())
            ->method('checkOut')
            ->with(1, $this->callback(function (array $data) {
                return isset($data['check_out_time']);
            }));

        $this->attendanceService->checkOut(1, ['staff_id' => 2]);
    }

    public function testCheckOutRecordNotFound(): void
    {
        $this->attendanceRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Attendance record not found');

        $this->attendanceService->checkOut(99, []);
    }

    public function testDelete(): void
    {
        $this->attendanceRepository->method('exists')->with(5)->willReturn(true);
        $this->attendanceRepository->expects($this->once())
            ->method('delete')
            ->with(5);

        $this->attendanceService->delete(5);
    }

    public function testDeleteRecordNotFound(): void
    {
        $this->attendanceRepository->method('exists')->with(99)->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Attendance record not found');

        $this->attendanceService->delete(99);
    }
}
