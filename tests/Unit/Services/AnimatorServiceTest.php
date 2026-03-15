<?php

namespace AnimaID\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use AnimaID\Services\AnimatorService;
use AnimaID\Repositories\AnimatorRepository;
use AnimaID\Config\ConfigManager;
use PDO;

class AnimatorServiceTest extends TestCase
{
    private $animatorRepository;
    private $config;
    private $db;
    private $animatorService;

    protected function setUp(): void
    {
        $this->animatorRepository = $this->createMock(AnimatorRepository::class);
        $this->config             = $this->createMock(ConfigManager::class);
        $this->db                 = $this->createMock(PDO::class);

        $this->animatorService = new AnimatorService(
            $this->animatorRepository,
            $this->config,
            $this->db
        );
    }

    public function testGetAllAnimators(): void
    {
        $mockAnimators = [
            ['id' => 1, 'first_name' => 'Alice', 'last_name' => 'Rossi', 'status' => 'active'],
            ['id' => 2, 'first_name' => 'Bob',   'last_name' => 'Bianchi', 'status' => 'active']
        ];

        $this->animatorRepository->method('getPaginated')->willReturn($mockAnimators);
        $this->animatorRepository->method('count')->willReturn(2);

        $result = $this->animatorService->getAnimators(1, 20, []);

        $this->assertCount(2, $result['animators']);
        $this->assertEquals(2, $result['pagination']['total']);
    }

    public function testGetAnimatorById(): void
    {
        $mockAnimator = [
            'id'         => 3,
            'first_name' => 'Carlo',
            'last_name'  => 'Verdi',
            'status'     => 'active'
        ];

        $this->animatorRepository->method('findById')->with(3)->willReturn($mockAnimator);
        $this->animatorRepository->method('getLinkedUsers')->willReturn([]);
        $this->animatorRepository->method('getDocuments')->willReturn([]);
        $this->animatorRepository->method('getNotes')->willReturn([]);

        $result = $this->animatorService->getAnimatorById(3);

        $this->assertNotNull($result);
        $this->assertEquals(3, $result['id']);
        $this->assertEquals('Carlo', $result['first_name']);
        $this->assertArrayHasKey('linked_users', $result);
    }

    public function testGetAnimatorNotFound(): void
    {
        $this->animatorRepository->method('findById')->willReturn(null);

        $result = $this->animatorService->getAnimatorById(999);

        $this->assertNull($result);
    }

    public function testCreateAnimator(): void
    {
        $this->animatorRepository->method('fiscalCodeExists')->willReturn(false);
        $this->animatorRepository->method('insert')->willReturn(10);

        $mockAnimator = [
            'id'         => 10,
            'first_name' => 'Diana',
            'last_name'  => 'Leone',
            'status'     => 'active'
        ];

        $this->animatorRepository->method('findById')->willReturn($mockAnimator);
        $this->animatorRepository->method('getLinkedUsers')->willReturn([]);
        $this->animatorRepository->method('getDocuments')->willReturn([]);
        $this->animatorRepository->method('getNotes')->willReturn([]);

        $result = $this->animatorService->createAnimator([
            'first_name' => 'Diana',
            'last_name'  => 'Leone'
        ], 1);

        $this->assertEquals(10, $result['id']);
        $this->assertEquals('Diana', $result['first_name']);
    }

    public function testCreateAnimatorMissingRequiredFields(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('First name is required');

        $this->animatorService->createAnimator(['last_name' => 'Leone'], 1);
    }

    public function testUpdateAnimator(): void
    {
        $existingAnimator = [
            'id'          => 5,
            'first_name'  => 'Old',
            'last_name'   => 'Name',
            'fiscal_code' => null,
            'status'      => 'active'
        ];

        $updatedAnimator = array_merge($existingAnimator, ['first_name' => 'Updated']);

        $this->animatorRepository->expects($this->exactly(2))
            ->method('findById')
            ->willReturnOnConsecutiveCalls($existingAnimator, $updatedAnimator);

        $this->animatorRepository->method('update')->willReturn(true);
        $this->animatorRepository->method('getLinkedUsers')->willReturn([]);
        $this->animatorRepository->method('getDocuments')->willReturn([]);
        $this->animatorRepository->method('getNotes')->willReturn([]);

        // Re-create service with the freshly configured mock
        $this->animatorService = new AnimatorService($this->animatorRepository, $this->config, $this->db);

        $result = $this->animatorService->updateAnimator(5, ['first_name' => 'Updated']);

        $this->assertEquals('Updated', $result['first_name']);
    }

    public function testDeleteAnimator(): void
    {
        $this->animatorRepository->method('exists')->with(5)->willReturn(true);
        $this->animatorRepository->expects($this->once())
            ->method('delete')
            ->with(5)
            ->willReturn(true);

        $result = $this->animatorService->deleteAnimator(5);

        $this->assertTrue($result);
    }

    public function testDeleteAnimatorNotFound(): void
    {
        $this->animatorRepository->method('exists')->with(999)->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Animator not found');

        $this->animatorService->deleteAnimator(999);
    }

    public function testLinkUser(): void
    {
        $this->animatorRepository->method('exists')->with(5)->willReturn(true);
        $this->animatorRepository->expects($this->once())
            ->method('linkUser')
            ->with(5, 7)
            ->willReturn(true);

        $result = $this->animatorService->linkUser(5, 7);

        $this->assertTrue($result);
    }

    public function testLinkUserAnimatorNotFound(): void
    {
        $this->animatorRepository->method('exists')->with(99)->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Animator not found');

        $this->animatorService->linkUser(99, 7);
    }
}
