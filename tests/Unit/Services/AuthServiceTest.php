<?php

namespace AnimaID\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use AnimaID\Services\AuthService;
use AnimaID\Repositories\UserRepository;
use AnimaID\Security\JwtManager;
use AnimaID\Config\ConfigManager;
use PDO;

class AuthServiceTest extends TestCase
{
    private $userRepository;
    private $jwtManager;
    private $config;
    private $db;
    private $authService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->jwtManager     = $this->createMock(JwtManager::class);
        $this->config         = $this->createMock(ConfigManager::class);
        $this->db             = $this->createMock(PDO::class);

        $this->authService = new AuthService(
            $this->userRepository,
            $this->jwtManager,
            $this->config,
            $this->db
        );
    }

    public function testLoginSuccess(): void
    {
        $hashedPassword = password_hash('Secret1', PASSWORD_BCRYPT);

        $mockUser = [
            'id'            => 1,
            'username'      => 'testuser',
            'email'         => 'test@example.com',
            'full_name'     => 'Test User',
            'password_hash' => $hashedPassword,
            'is_active'     => 1
        ];

        $this->userRepository->method('findByUsername')->with('testuser')->willReturn($mockUser);
        $this->userRepository->method('findWithRoles')->with(1)->willReturn(
            array_merge($mockUser, ['roles' => [['name' => 'animator']]])
        );
        $this->userRepository->expects($this->once())->method('updateLastLogin')->with(1);

        $this->jwtManager->method('generateToken')->willReturn([
            'token'      => 'jwt.token.here',
            'expires_at' => '2026-03-15 12:00:00'
        ]);

        $result = $this->authService->login('testuser', 'Secret1');

        $this->assertTrue($result['success']);
        $this->assertEquals('jwt.token.here', $result['token']);
        $this->assertEquals(1, $result['user']['id']);
        $this->assertEquals('testuser', $result['user']['username']);
        $this->assertContains('animator', $result['user']['roles']);
    }

    public function testLoginWrongPassword(): void
    {
        $hashedPassword = password_hash('CorrectPass1', PASSWORD_BCRYPT);

        $mockUser = [
            'id'            => 1,
            'username'      => 'testuser',
            'email'         => 'test@example.com',
            'full_name'     => 'Test User',
            'password_hash' => $hashedPassword,
            'is_active'     => 1
        ];

        $this->userRepository->method('findByUsername')->with('testuser')->willReturn($mockUser);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->authService->login('testuser', 'WrongPass1');
    }

    public function testLoginUserNotFound(): void
    {
        $this->userRepository->method('findByUsername')->with('nobody')->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->authService->login('nobody', 'SomePass1');
    }

    public function testLoginInactiveUser(): void
    {
        $hashedPassword = password_hash('Secret1', PASSWORD_BCRYPT);

        $mockUser = [
            'id'            => 2,
            'username'      => 'inactiveuser',
            'email'         => 'inactive@example.com',
            'full_name'     => 'Inactive User',
            'password_hash' => $hashedPassword,
            'is_active'     => 0
        ];

        $this->userRepository->method('findByUsername')->with('inactiveuser')->willReturn($mockUser);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Account is disabled');

        $this->authService->login('inactiveuser', 'Secret1');
    }

    public function testVerifyToken(): void
    {
        $decoded = new \stdClass();
        $decoded->sub = 5;

        $mockUser = [
            'id'        => 5,
            'username'  => 'validuser',
            'email'     => 'valid@example.com',
            'full_name' => 'Valid User',
            'is_active' => 1
        ];

        $this->jwtManager->method('validateToken')->with('valid.token')->willReturn($decoded);
        $this->userRepository->method('findById')->with(5)->willReturn($mockUser);

        $result = $this->authService->verifyToken('valid.token');

        $this->assertNotNull($result);
        $this->assertEquals(5, $result['id']);
        $this->assertEquals('validuser', $result['username']);
    }

    public function testVerifyTokenExpired(): void
    {
        $this->jwtManager->method('validateToken')->with('expired.token')->willReturn(null);

        $result = $this->authService->verifyToken('expired.token');

        $this->assertNull($result);
    }

    public function testLogout(): void
    {
        $this->jwtManager->expects($this->once())
            ->method('revokeToken')
            ->with('some.token')
            ->willReturn(true);

        $result = $this->authService->logout('some.token');

        $this->assertTrue($result);
    }
}
