<?php

namespace AnimaID\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use AnimaID\Services\SpaceService;
use AnimaID\Repositories\SpaceRepository;
use AnimaID\Config\ConfigManager;

class SpaceServiceTest extends TestCase
{
    private $spaceRepository;
    private $config;
    private $spaceService;

    protected function setUp(): void
    {
        $this->spaceRepository = $this->createMock(SpaceRepository::class);
        $this->config          = $this->createMock(ConfigManager::class);

        $this->spaceService = new SpaceService(
            $this->spaceRepository,
            $this->config
        );
    }

    public function testGetAllSpacesActiveOnly(): void
    {
        $mockSpaces = [
            ['id' => 1, 'name' => 'Hall A', 'is_active' => 1],
            ['id' => 2, 'name' => 'Room B', 'is_active' => 1]
        ];

        $this->spaceRepository->expects($this->once())
            ->method('findAllActive')
            ->willReturn($mockSpaces);

        $result = $this->spaceService->getAllSpaces(true);

        $this->assertCount(2, $result);
    }

    public function testGetAllSpacesIncludeInactive(): void
    {
        $mockSpaces = [
            ['id' => 1, 'name' => 'Hall A', 'is_active' => 1],
            ['id' => 3, 'name' => 'Old Room', 'is_active' => 0]
        ];

        $this->spaceRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($mockSpaces);

        $result = $this->spaceService->getAllSpaces(false);

        $this->assertCount(2, $result);
    }

    public function testGetSpace(): void
    {
        $mockSpace = ['id' => 5, 'name' => 'Conference Room', 'capacity' => 30];

        $this->spaceRepository->method('findById')->with(5)->willReturn($mockSpace);

        $result = $this->spaceService->getSpace(5);

        $this->assertNotNull($result);
        $this->assertEquals('Conference Room', $result['name']);
    }

    public function testGetSpaceNotFound(): void
    {
        $this->spaceRepository->method('findById')->with(999)->willReturn(null);

        $result = $this->spaceService->getSpace(999);

        $this->assertNull($result);
    }

    public function testCreateSpace(): void
    {
        $this->spaceRepository->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (array $data) {
                return $data['name'] === 'New Hall'
                    && $data['capacity'] === 50
                    && $data['is_active'] === 1;
            }))
            ->willReturn(7);

        $result = $this->spaceService->createSpace([
            'name'     => 'New Hall',
            'capacity' => 50
        ]);

        $this->assertEquals(7, $result);
    }

    public function testCreateSpaceMissingName(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Space name is required');

        $this->spaceService->createSpace(['capacity' => 20]);
    }

    public function testCreateSpaceNegativeCapacity(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Capacity cannot be negative');

        $this->spaceService->createSpace(['name' => 'Bad Room', 'capacity' => -5]);
    }

    public function testUpdateSpace(): void
    {
        $this->spaceRepository->method('exists')->with(3)->willReturn(true);
        $this->spaceRepository->expects($this->once())
            ->method('update')
            ->with(3, $this->callback(function (array $data) {
                return $data['name'] === 'Updated Hall';
            }));

        $this->spaceService->updateSpace(3, ['name' => 'Updated Hall']);
    }

    public function testUpdateSpaceNotFound(): void
    {
        $this->spaceRepository->method('exists')->with(999)->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Space not found');

        $this->spaceService->updateSpace(999, ['name' => 'Ghost Room']);
    }

    public function testDeleteSpace(): void
    {
        $this->spaceRepository->method('exists')->with(4)->willReturn(true);
        $this->spaceRepository->expects($this->once())
            ->method('delete')
            ->with(4);

        $this->spaceService->deleteSpace(4);
    }

    public function testDeleteSpaceNotFound(): void
    {
        $this->spaceRepository->method('exists')->with(999)->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Space not found');

        $this->spaceService->deleteSpace(999);
    }

    public function testCreateBooking(): void
    {
        $this->spaceRepository->method('exists')->with(2)->willReturn(true);
        $this->spaceRepository->method('checkHierarchyOverlap')->willReturn(null); // no conflict

        $this->spaceRepository->expects($this->once())
            ->method('createBooking')
            ->with($this->callback(function (array $data) {
                return $data['space_id'] === 2
                    && $data['status'] === 'confirmed';
            }))
            ->willReturn(15);

        $result = $this->spaceService->createBooking([
            'space_id'   => 2,
            'start_time' => '2026-03-15 09:00:00',
            'end_time'   => '2026-03-15 11:00:00',
            'purpose'    => 'Team meeting'
        ], 1);

        $this->assertEquals(15, $result);
    }

    public function testCreateBookingConflictDetected(): void
    {
        $this->spaceRepository->method('exists')->with(2)->willReturn(true);
        $this->spaceRepository->method('checkHierarchyOverlap')->willReturn([
            'id'   => 2,
            'name' => 'Conference Room'
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Conflict detected/');

        $this->spaceService->createBooking([
            'space_id'   => 2,
            'start_time' => '2026-03-15 09:00:00',
            'end_time'   => '2026-03-15 11:00:00'
        ], 1);
    }

    public function testCreateBookingEndBeforeStart(): void
    {
        $this->spaceRepository->method('exists')->with(2)->willReturn(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('End time must be after start time');

        $this->spaceService->createBooking([
            'space_id'   => 2,
            'start_time' => '2026-03-15 11:00:00',
            'end_time'   => '2026-03-15 09:00:00'
        ], 1);
    }

    public function testCreateBookingSpaceNotFound(): void
    {
        $this->spaceRepository->method('exists')->with(999)->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Space not found');

        $this->spaceService->createBooking([
            'space_id'   => 999,
            'start_time' => '2026-03-15 09:00:00',
            'end_time'   => '2026-03-15 11:00:00'
        ], 1);
    }
}
