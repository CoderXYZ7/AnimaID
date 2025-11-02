<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/JWT.php';

/**
 * Authentication and Authorization Class
 * Handles user authentication, JWT tokens, and permission checking
 */

class Auth {
    private $db;
    private $config;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../config.php';
    }

    /**
     * Authenticate user and return JWT token
     */
    public function login(string $username, string $password): array {
        // Find user by username or email
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1",
            [$username, $username]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception("Invalid credentials");
        }

        // Update last login
        $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

        // Get user roles
        $roles = $this->getUserRoles($user['id']);

        // Generate JWT token
        $token = $this->generateToken($user, $roles);

        // Store session
        $this->createSession($user['id'], $token);

        return [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'roles' => $roles
            ],
            'expires_at' => date('c', strtotime("+{$this->config['jwt']['expiration_hours']} hours"))
        ];
    }

    /**
     * Verify JWT token and return user data
     */
    public function verifyToken(string $token): array {
        try {
            $decoded = JWT::decode($token, $this->config['jwt']['secret']);

            // Check if session exists and is valid
            $session = $this->db->fetchOne(
                "SELECT * FROM user_sessions WHERE session_token = ? AND expires_at > datetime('now')",
                [$token]
            );

            if (!$session) {
                throw new Exception("Invalid session");
            }

            // Get current user roles (in case they changed)
            $user = $this->db->fetchOne("SELECT * FROM users WHERE id = ? AND is_active = 1", [$decoded->user_id]);
            if (!$user) {
                throw new Exception("User not found or inactive");
            }

            $roles = $this->getUserRoles($user['id']);

            return [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'roles' => $roles
            ];

        } catch (Exception $e) {
            throw new Exception("Invalid token: " . $e->getMessage());
        }
    }

    /**
     * Check if user has specific permission
     */
    public function checkPermission(int $userId, string $permission): bool {
        // Get all user roles
        $userRoles = $this->db->fetchAll(
            "SELECT r.id, r.name FROM roles r INNER JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ?",
            [$userId]
        );

        if (empty($userRoles)) {
            return false;
        }

        // Check if any role has the permission
        $roleIds = array_column($userRoles, 'id');
        $placeholders = str_repeat('?,', count($roleIds) - 1) . '?';

        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM role_permissions rp INNER JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_id IN ($placeholders) AND p.name = ?",
            array_merge($roleIds, [$permission])
        );

        return $result['count'] > 0;
    }

    /**
     * Get all roles for a user
     */
    public function getUserRoles(int $userId): array {
        return $this->db->fetchAll(
            "SELECT r.*, ur.is_primary FROM roles r INNER JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ? ORDER BY ur.is_primary DESC",
            [$userId]
        );
    }

    /**
     * Logout user by invalidating session
     */
    public function logout(string $token): void {
        $this->db->delete('user_sessions', 'session_token = ?', [$token]);
    }

    /**
     * Create a new user
     */
    public function createUser(string $username, string $email, string $password, array $roleIds = []): int {
        // Validate password
        $this->validatePassword($password);

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, [
            'cost' => $this->config['security']['bcrypt_cost']
        ]);

        // Create user
        $userId = $this->db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash
        ]);

        // Assign roles
        if (!empty($roleIds)) {
            $this->assignRolesToUser($userId, $roleIds, 1); // User ID 1 is the creator (admin)
        }

        return $userId;
    }

    /**
     * Update user information
     */
    public function updateUser(int $userId, array $data): void {
        $allowedFields = ['email', 'is_active'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (!empty($updateData)) {
            $this->db->update('users', $updateData, 'id = ?', [$userId]);
        }

        // Update roles if provided
        if (isset($data['role_ids'])) {
            $this->assignRolesToUser($userId, $data['role_ids'], 1);
        }
    }

    /**
     * Assign roles to user
     */
    public function assignRolesToUser(int $userId, array $roleIds, int $assignedBy): void {
        // Remove existing roles
        $this->db->delete('user_roles', 'user_id = ?', [$userId]);

        // Assign new roles
        foreach ($roleIds as $roleId) {
            $this->db->insert('user_roles', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'assigned_by' => $assignedBy,
                'is_primary' => ($roleId == $roleIds[0] ? 1 : 0) // First role is primary
            ]);
        }
    }

    /**
     * Get all users with pagination
     */
    public function getUsers(int $page = 1, int $limit = 20, string $search = ''): array {
        $offset = ($page - 1) * $limit;

        $whereClause = '';
        $params = [];

        if (!empty($search)) {
            $whereClause = 'WHERE username LIKE ? OR email LIKE ?';
            $params = ["%{$search}%", "%{$search}%"];
        }

        // Get users
        $users = $this->db->fetchAll(
            "SELECT id, username, email, is_active, created_at, last_login FROM users {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        );

        // Add roles to each user
        foreach ($users as &$user) {
            $user['roles'] = array_column($this->getUserRoles($user['id']), 'name');
        }

        // Get total count
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM users {$whereClause}",
            $params
        )['count'];

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
     * Get all roles
     */
    public function getRoles(): array {
        $roles = $this->db->fetchAll("SELECT * FROM roles ORDER BY name");

        // Add permissions to each role
        foreach ($roles as &$role) {
            $role['permissions'] = $this->db->fetchAll(
                "SELECT p.name FROM permissions p INNER JOIN role_permissions rp ON p.id = rp.permission_id WHERE rp.role_id = ?",
                [$role['id']]
            );
            $role['permissions'] = array_column($role['permissions'], 'name');
        }

        return $roles;
    }

    /**
     * Get all permissions grouped by module
     */
    public function getPermissions(): array {
        $permissions = $this->db->fetchAll("SELECT * FROM permissions ORDER BY module, name");

        $grouped = [];
        foreach ($permissions as $permission) {
            $grouped[$permission['module']][] = $permission;
        }

        return $grouped;
    }

    /**
     * Validate password strength
     */
    private function validatePassword(string $password): void {
        $config = $this->config['security'];

        if (strlen($password) < $config['password_min_length']) {
            throw new Exception("Password must be at least {$config['password_min_length']} characters long");
        }

        if ($config['password_require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            throw new Exception("Password must contain at least one uppercase letter");
        }

        if ($config['password_require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            throw new Exception("Password must contain at least one lowercase letter");
        }

        if ($config['password_require_numbers'] && !preg_match('/[0-9]/', $password)) {
            throw new Exception("Password must contain at least one number");
        }
    }

    /**
     * Generate JWT token
     */
    private function generateToken(array $user, array $roles): string {
        $payload = [
            'iss' => $this->config['system']['name'],
            'aud' => $this->config['system']['name'],
            'iat' => time(),
            'exp' => time() + ($this->config['jwt']['expiration_hours'] * 3600),
            'user_id' => $user['id'],
            'username' => $user['username'],
            'roles' => array_column($roles, 'name')
        ];

        return JWT::encode($payload, $this->config['jwt']['secret'], 'HS256');
    }

    /**
     * Create session record
     */
    private function createSession(int $userId, string $token): void {
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$this->config['jwt']['expiration_hours']} hours"));

        $this->db->insert('user_sessions', [
            'user_id' => $userId,
            'session_token' => $token,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'expires_at' => $expiresAt
        ]);
    }
}
