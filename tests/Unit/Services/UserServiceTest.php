<?php

namespace AnimaID\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use AnimaID\Services\UserService;
use AnimaID\Repositories\UserRepository;
use AnimaID\Config\ConfigManager;
use PDO;

class UserServiceTest extends TestCase
{
    private $userRepository;
    private $config;
    private $db;
    private $userService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->config         = $this->createMock(ConfigManager::class);
        $this->db             = $this->createMock(PDO::class);

        // Return sensible defaults for password validation config keys
        $this->config->method('get')->willReturnCallback(function (string $key, $default = null) {
            $map = [
                'security.bcrypt_cost'              => 4,  // low cost for tests
                'security.password_min_length'      => 8,
                'security.password_require_uppercase' => true,
                'security.password_require_lowercase' => true,
                'security.password_require_numbers'   => true,
                'security.password_require_symbols'   => false,
            ];
            return $map[$key] ?? $default;
        });

        $this->userService = new UserService(
            $this->userRepository,
            $this->config,
            $this->db
        );
    }

    public function testCreateUserSuccess(): void
    {
        $this->userRepository->method('usernameExists')->willReturn(false);
        $this->userRepository->method('emailExists')->willReturn(false);
        $this->userRepository->method('insert')->willReturn(10);
        $this->userRepository->method('findWithRoles')->with(10)->willReturn([
            'id'        => 10,
            'username'  => 'newuser',
            'email'     => 'new@example.com',
            'full_name' => 'New User',
            'roles'     => []
        ]);

        $result = $this->userService->createUser([
            'username'  => 'newuser',
            'email'     => 'new@example.com',
            'password'  => 'SecurePass1',
            'full_name' => 'New User'
        ]);

        $this->assertEquals(10, $result['id']);
        $this->assertEquals('newuser', $result['username']);
        // password_hash must NOT be exposed in the returned data
        $this->assertArrayNotHasKey('password', $result);
        $this->assertArrayNotHasKey('password_hash', $result);
    }

    public function testCreateUserDuplicateEmail(): void
    {
        $this->userRepository->method('usernameExists')->willReturn(false);
        $this->userRepository->method('emailExists')->willReturn(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email already exists');

        $this->userService->createUser([
            'username' => 'anotheruser',
            'email'    => 'existing@example.com',
            'password' => 'SecurePass1'
        ]);
    }

    public function testCreateUserDuplicateUsername(): void
    {
        $this->userRepository->method('usernameExists')->willReturn(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Username already exists');

        $this->userService->createUser([
            'username' => 'existinguser',
            'email'    => 'unique@example.com',
            'password' => 'SecurePass1'
        ]);
    }

    public function testUpdateUser(): void
    {
        $existingUser = [
            'id'       => 3,
            'username' => 'oldname',
            'email'    => 'old@example.com',
            'is_active' => 1
        ];

        $this->userRepository->method('findById')->with(3)->willReturn($existingUser);
        $this->userRepository->method('usernameExists')->willReturn(false);
        $this->userRepository->method('emailExists')->willReturn(false);
        $this->userRepository->method('update')->willReturn(true);
        $this->userRepository->method('findWithRoles')->with(3)->willReturn([
            'id'       => 3,
            'username' => 'newname',
            'email'    => 'new@example.com',
            'roles'    => []
        ]);

        $result = $this->userService->updateUser(3, [
            'username' => 'newname',
            'email'    => 'new@example.com'
        ]);

        $this->assertEquals('newname', $result['username']);
        $this->assertEquals('new@example.com', $result['email']);
    }

    public function testChangePasswordSuccess(): void
    {
        $oldHash = password_hash('OldPass1', PASSWORD_BCRYPT);

        $mockUser = [
            'id'            => 4,
            'password_hash' => $oldHash
        ];

        $this->userRepository->method('findById')->with(4)->willReturn($mockUser);
        $this->userRepository->expects($this->once())
            ->method('update')
            ->with(4, $this->callback(function (array $data) {
                // Must save a password_hash, not a plain-text password
                return isset($data['password_hash']) && !isset($data['password']);
            }))
            ->willReturn(true);

        $result = $this->userService->changePassword(4, 'OldPass1', 'NewPass1');

        $this->assertTrue($result);
    }

    public function testChangePasswordWrongOldPassword(): void
    {
        $oldHash = password_hash('RealOldPass1', PASSWORD_BCRYPT);

        $this->userRepository->method('findById')->with(4)->willReturn([
            'id'            => 4,
            'password_hash' => $oldHash
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Current password is incorrect');

        $this->userService->changePassword(4, 'WrongOldPass1', 'NewPass1');
    }

    public function testDeleteUser(): void
    {
        $this->userRepository->method('exists')->with(7)->willReturn(true);
        $this->userRepository->expects($this->once())
            ->method('delete')
            ->with(7)
            ->willReturn(true);

        $result = $this->userService->deleteUser(7);

        $this->assertTrue($result);
    }

    public function testDeleteUserNotFound(): void
    {
        $this->userRepository->method('exists')->with(99)->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User not found');

        $this->userService->deleteUser(99);
    }
}
