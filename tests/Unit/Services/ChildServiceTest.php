<?php

namespace AnimaID\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use AnimaID\Services\ChildService;
use AnimaID\Repositories\ChildRepository;
use AnimaID\Config\ConfigManager;
use PDO;

class ChildServiceTest extends TestCase
{
    private $childRepository;
    private $config;
    private $db;
    private $childService;

    protected function setUp(): void
    {
        $this->childRepository = $this->createMock(ChildRepository::class);
        $this->config          = $this->createMock(ConfigManager::class);
        $this->db              = $this->createMock(PDO::class);

        $this->childService = new ChildService(
            $this->childRepository,
            $this->config,
            $this->db
        );
    }

    public function testGetAllChildren(): void
    {
        $mockChildren = [
            ['id' => 1, 'first_name' => 'Alice', 'last_name' => 'Smith'],
            ['id' => 2, 'first_name' => 'Bob',   'last_name' => 'Jones']
        ];

        $this->childRepository->method('getPaginated')->willReturn($mockChildren);
        $this->childRepository->method('count')->willReturn(2);

        $result = $this->childService->getChildren(1, 20, []);

        $this->assertCount(2, $result['children']);
        $this->assertEquals(2, $result['pagination']['total']);
    }

    public function testGetChildById(): void
    {
        $mockChild = ['id' => 5, 'first_name' => 'Charlie', 'last_name' => 'Brown', 'birth_date' => '2015-06-01'];

        $this->childRepository->method('findById')->with(5)->willReturn($mockChild);
        $this->childRepository->method('getGuardians')->willReturn([]);
        $this->childRepository->method('getDocuments')->willReturn([]);
        $this->childRepository->method('getNotes')->willReturn([]);

        $result = $this->childService->getChildById(5);

        $this->assertNotNull($result);
        $this->assertEquals(5, $result['id']);
        $this->assertEquals('Charlie', $result['first_name']);
        $this->assertArrayHasKey('guardians', $result);
        $this->assertArrayHasKey('documents', $result);
        $this->assertArrayHasKey('notes', $result);
    }

    public function testGetChildNotFound(): void
    {
        $this->childRepository->method('findById')->willReturn(null);

        $result = $this->childService->getChildById(999);

        $this->assertNull($result);
    }

    public function testCreateChild(): void
    {
        $this->childRepository->method('fiscalCodeExists')->willReturn(false);
        $this->childRepository->method('insert')->willReturn(20);

        // findById called twice: once inside createChild -> getChildById
        $mockChild = [
            'id'         => 20,
            'first_name' => 'Daisy',
            'last_name'  => 'Duck',
            'birth_date' => '2018-04-10'
        ];
        $this->childRepository->method('findById')->willReturn($mockChild);
        $this->childRepository->method('getGuardians')->willReturn([]);
        $this->childRepository->method('getDocuments')->willReturn([]);
        $this->childRepository->method('getNotes')->willReturn([]);

        $result = $this->childService->createChild([
            'first_name' => 'Daisy',
            'last_name'  => 'Duck',
            'birth_date' => '2018-04-10'
        ], 1);

        $this->assertEquals(20, $result['id']);
        $this->assertEquals('Daisy', $result['first_name']);
    }

    public function testCreateChildMissingRequiredFields(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('First name is required');

        $this->childService->createChild([
            'last_name'  => 'Duck',
            'birth_date' => '2018-04-10'
        ], 1);
    }

    public function testUpdateChild(): void
    {
        $existingChild = [
            'id'          => 5,
            'first_name'  => 'Old',
            'last_name'   => 'Name',
            'birth_date'  => '2015-01-01',
            'fiscal_code' => null
        ];

        $updatedChild = array_merge($existingChild, ['first_name' => 'New']);

        $this->childRepository->method('findById')->willReturn($existingChild);
        $this->childRepository->method('fiscalCodeExists')->willReturn(false);
        $this->childRepository->method('update')->willReturn(true);
        $this->childRepository->method('getGuardians')->willReturn([]);
        $this->childRepository->method('getDocuments')->willReturn([]);
        $this->childRepository->method('getNotes')->willReturn([]);

        // Second call to findById (inside getChildById after update) needs to return updated data
        // We use a counter to return the updated version on the second call
        $callCount = 0;
        $this->childRepository = $this->createMock(ChildRepository::class);
        $this->childRepository->expects($this->exactly(2))
            ->method('findById')
            ->willReturnOnConsecutiveCalls($existingChild, $updatedChild);
        $this->childRepository->method('update')->willReturn(true);
        $this->childRepository->method('getGuardians')->willReturn([]);
        $this->childRepository->method('getDocuments')->willReturn([]);
        $this->childRepository->method('getNotes')->willReturn([]);

        $this->childService = new ChildService($this->childRepository, $this->config, $this->db);

        $result = $this->childService->updateChild(5, ['first_name' => 'New']);

        $this->assertEquals('New', $result['first_name']);
    }

    public function testDeleteChild(): void
    {
        $this->childRepository->method('exists')->with(5)->willReturn(true);
        $this->childRepository->expects($this->once())
            ->method('delete')
            ->with(5)
            ->willReturn(true);

        $result = $this->childService->deleteChild(5);

        $this->assertTrue($result);
    }

    public function testDeleteChildNotFound(): void
    {
        $this->childRepository->method('exists')->with(999)->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Child not found');

        $this->childService->deleteChild(999);
    }
}
