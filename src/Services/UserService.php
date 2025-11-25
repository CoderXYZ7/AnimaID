<?php

namespace AnimaID\Services;

use AnimaID\Repositories\UserRepository;
use AnimaID\Config\ConfigManager;
use PDO;

/**
 * User Service
 * Handles user management operations
 */
class UserService
{
    private UserRepository $userRepository;
    private ConfigManager $config;
    private PDO $db;

    public function __construct(
        UserRepository $userRepository,
        ConfigManager $config,
        PDO $db
    ) {
        $this->userRepository = $userRepository;
        $this->config = $config;
        $this->db = $db;
    }

    /**
     * Get all users with pagination
     */
    public function getUsers(int $page = 1, int $limit = 20, string $search = ''): array
    {
        $users = $this->userRepository->getPaginated($page, $limit, $search);
        $total = $this->userRepository->count();

        return [
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Get user by ID
     */
    public function getUserById(int $userId): ?array
    {
        return $this->userRepository->findWithRoles($userId);
    }

    /**
     * Create a new user
     */
    public function createUser(array $data): array
    {
        // Validate required fields
        $this->validateUserData($data, true);

        // Check if username exists
        if ($this->userRepository->usernameExists($data['username'])) {
            throw new \Exception('Username already exists');
        }

        // Check if email exists
        if ($this->userRepository->emailExists($data['email'])) {
            throw new \Exception('Email already exists');
        }

        // Hash password
        $bcryptCost = $this->config->get('security.bcrypt_cost', 12);
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => $bcryptCost]);

        // Prepare user data
        $userData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'full_name' => $data['full_name'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Insert user
        $userId = $this->userRepository->insert($userData);

        // Assign roles if provided
        if (!empty($data['role_ids'])) {
            $roleService = new RoleService(
                new \AnimaID\Repositories\RoleRepository($this->db),
                $this->config,
                $this->db
            );

            foreach ($data['role_ids'] as $roleId) {
                $roleService->assignRoleToUser($roleId, $userId, $data['created_by'] ?? 1);
            }
        }

        return $this->getUserById($userId);
    }

    /**
     * Update user
     */
    public function updateUser(int $userId, array $data): array
    {
        // Check if user exists
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Validate data
        $this->validateUserData($data, false);

        // Check username uniqueness if changed
        if (isset($data['username']) && $data['username'] !== $user['username']) {
            if ($this->userRepository->usernameExists($data['username'], $userId)) {
                throw new \Exception('Username already exists');
            }
        }

        // Check email uniqueness if changed
        if (isset($data['email']) && $data['email'] !== $user['email']) {
            if ($this->userRepository->emailExists($data['email'], $userId)) {
                throw new \Exception('Email already exists');
            }
        }

        // Prepare update data
        $updateData = [];
        $allowedFields = ['username', 'email', 'full_name', 'is_active'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        // Hash new password if provided
        if (!empty($data['password'])) {
            $bcryptCost = $this->config->get('security.bcrypt_cost', 12);
            $updateData['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => $bcryptCost]);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        // Update user
        $this->userRepository->update($userId, $updateData);

        return $this->getUserById($userId);
    }

    /**
     * Delete user
     */
    public function deleteUser(int $userId): bool
    {
        // Check if user exists
        if (!$this->userRepository->exists($userId)) {
            throw new \Exception('User not found');
        }

        return $this->userRepository->delete($userId);
    }

    /**
     * Change user password
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new \Exception('User not found');
        }

        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            throw new \Exception('Current password is incorrect');
        }

        // Validate new password
        $this->validatePassword($newPassword);

        // Hash new password
        $bcryptCost = $this->config->get('security.bcrypt_cost', 12);
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => $bcryptCost]);

        return $this->userRepository->update($userId, [
            'password' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Validate user data
     */
    private function validateUserData(array $data, bool $isCreate): void
    {
        if ($isCreate) {
            if (empty($data['username'])) {
                throw new \Exception('Username is required');
            }
            if (empty($data['email'])) {
                throw new \Exception('Email is required');
            }
            if (empty($data['password'])) {
                throw new \Exception('Password is required');
            }
        }

        // Validate email format
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid email format');
        }

        // Validate password if provided
        if (isset($data['password'])) {
            $this->validatePassword($data['password']);
        }
    }

    /**
     * Validate password against security requirements
     */
    private function validatePassword(string $password): void
    {
        $minLength = $this->config->get('security.password_min_length', 8);

        if (strlen($password) < $minLength) {
            throw new \Exception("Password must be at least {$minLength} characters long");
        }

        if ($this->config->get('security.password_require_uppercase', true)) {
            if (!preg_match('/[A-Z]/', $password)) {
                throw new \Exception('Password must contain at least one uppercase letter');
            }
        }

        if ($this->config->get('security.password_require_lowercase', true)) {
            if (!preg_match('/[a-z]/', $password)) {
                throw new \Exception('Password must contain at least one lowercase letter');
            }
        }

        if ($this->config->get('security.password_require_numbers', true)) {
            if (!preg_match('/[0-9]/', $password)) {
                throw new \Exception('Password must contain at least one number');
            }
        }

        if ($this->config->get('security.password_require_symbols', false)) {
            if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                throw new \Exception('Password must contain at least one special character');
            }
        }
    }

    /**
     * Get user statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->userRepository->count(),
            'active' => $this->userRepository->countActive()
        ];
    }
}
