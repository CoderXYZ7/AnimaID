<?php

namespace AnimaID\Repositories;

use PDO;

/**
 * User Repository
 * Handles all user data access operations
 */
class UserRepository extends BaseRepository
{
    protected string $table = 'users';

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        return $this->queryOne(
            "SELECT * FROM {$this->table} WHERE username = ?",
            [$username]
        );
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->queryOne(
            "SELECT * FROM {$this->table} WHERE email = ?",
            [$email]
        );
    }

    /**
     * Search users by username or email
     */
    public function search(string $searchTerm, int $limit = 20, int $offset = 0): array
    {
        return $this->query(
            "SELECT * FROM {$this->table} 
             WHERE username LIKE ? OR email LIKE ? OR full_name LIKE ?
             ORDER BY username
             LIMIT ? OFFSET ?",
            ["%{$searchTerm}%", "%{$searchTerm}%", "%{$searchTerm}%", $limit, $offset]
        );
    }

    /**
     * Get users with pagination
     */
    public function getPaginated(int $page = 1, int $limit = 20, string $search = ''): array
    {
        $offset = ($page - 1) * $limit;

        if ($search) {
            return $this->search($search, $limit, $offset);
        }

        return $this->query(
            "SELECT * FROM {$this->table} ORDER BY username LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Get user with their roles
     */
    public function findWithRoles(int $userId): ?array
    {
        $user = $this->findById($userId);
        
        if (!$user) {
            return null;
        }

        $user['roles'] = $this->query(
            "SELECT r.* FROM roles r
             INNER JOIN user_roles ur ON r.id = ur.role_id
             WHERE ur.user_id = ?",
            [$userId]
        );

        return $user;
    }

    /**
     * Get user with their permissions
     */
    public function findWithPermissions(int $userId): ?array
    {
        $user = $this->findById($userId);
        
        if (!$user) {
            return null;
        }

        $user['permissions'] = $this->query(
            "SELECT DISTINCT p.* FROM permissions p
             INNER JOIN role_permissions rp ON p.id = rp.permission_id
             INNER JOIN user_roles ur ON rp.role_id = ur.role_id
             WHERE ur.user_id = ?
             ORDER BY p.category, p.name",
            [$userId]
        );

        return $user;
    }

    /**
     * Check if username exists
     */
    public function usernameExists(string $username, ?int $excludeUserId = null): bool
    {
        if ($excludeUserId) {
            $result = $this->queryOne(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE username = ? AND id != ?",
                [$username, $excludeUserId]
            );
        } else {
            $result = $this->queryOne(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE username = ?",
                [$username]
            );
        }

        return $result['count'] > 0;
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        if ($excludeUserId) {
            $result = $this->queryOne(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ? AND id != ?",
                [$email, $excludeUserId]
            );
        } else {
            $result = $this->queryOne(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ?",
                [$email]
            );
        }

        return $result['count'] > 0;
    }

    /**
     * Update user's last login timestamp
     */
    public function updateLastLogin(int $userId): bool
    {
        return $this->update($userId, [
            'last_login' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get active sessions for a user
     */
    public function getActiveSessions(int $userId): array
    {
        return $this->query(
            "SELECT * FROM user_sessions 
             WHERE user_id = ? AND expires_at > datetime('now')
             ORDER BY created_at DESC",
            [$userId]
        );
    }

    /**
     * Count active users
     */
    public function countActive(): int
    {
        $result = $this->queryOne(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE is_active = 1"
        );
        return (int) $result['count'];
    }
}
