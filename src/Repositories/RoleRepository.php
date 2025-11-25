<?php

namespace AnimaID\Repositories;

use PDO;

/**
 * Role Repository
 * Handles all role and permission data access operations
 */
class RoleRepository extends BaseRepository
{
    protected string $table = 'roles';

    /**
     * Find role by name
     */
    public function findByName(string $name): ?array
    {
        return $this->queryOne(
            "SELECT * FROM {$this->table} WHERE name = ?",
            [$name]
        );
    }

    /**
     * Get all roles with their permissions
     */
    public function getAllWithPermissions(): array
    {
        $roles = $this->findAll();

        foreach ($roles as &$role) {
            $role['permissions'] = $this->getPermissions($role['id']);
        }

        return $roles;
    }

    /**
     * Get role with permissions
     */
    public function findWithPermissions(int $roleId): ?array
    {
        $role = $this->findById($roleId);
        
        if (!$role) {
            return null;
        }

        $role['permissions'] = $this->getPermissions($roleId);

        return $role;
    }

    /**
     * Get permissions for a role
     */
    public function getPermissions(int $roleId): array
    {
        return $this->query(
            "SELECT p.* FROM permissions p
             INNER JOIN role_permissions rp ON p.id = rp.permission_id
             WHERE rp.role_id = ?
             ORDER BY p.category, p.name",
            [$roleId]
        );
    }

    /**
     * Assign permissions to role
     */
    public function assignPermissions(int $roleId, array $permissionIds): bool
    {
        // First, remove all existing permissions
        $this->db->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$roleId]);

        // Then add new permissions
        $stmt = $this->db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        
        foreach ($permissionIds as $permissionId) {
            $stmt->execute([$roleId, $permissionId]);
        }

        return true;
    }

    /**
     * Add single permission to role
     */
    public function addPermission(int $roleId, int $permissionId): bool
    {
        $stmt = $this->db->prepare(
            "INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)"
        );
        return $stmt->execute([$roleId, $permissionId]);
    }

    /**
     * Remove permission from role
     */
    public function removePermission(int $roleId, int $permissionId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?"
        );
        return $stmt->execute([$roleId, $permissionId]);
    }

    /**
     * Get users with this role
     */
    public function getUsers(int $roleId): array
    {
        return $this->query(
            "SELECT u.* FROM users u
             INNER JOIN user_roles ur ON u.id = ur.user_id
             WHERE ur.role_id = ?
             ORDER BY u.username",
            [$roleId]
        );
    }

    /**
     * Assign role to user
     */
    public function assignToUser(int $roleId, int $userId, int $assignedBy): bool
    {
        $stmt = $this->db->prepare(
            "INSERT OR IGNORE INTO user_roles (user_id, role_id, assigned_by) VALUES (?, ?, ?)"
        );
        return $stmt->execute([$userId, $roleId, $assignedBy]);
    }

    /**
     * Remove role from user
     */
    public function removeFromUser(int $roleId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM user_roles WHERE user_id = ? AND role_id = ?"
        );
        return $stmt->execute([$userId, $roleId]);
    }

    /**
     * Check if role name exists
     */
    public function nameExists(string $name, ?int $excludeRoleId = null): bool
    {
        if ($excludeRoleId) {
            $result = $this->queryOne(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE name = ? AND id != ?",
                [$name, $excludeRoleId]
            );
        } else {
            $result = $this->queryOne(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE name = ?",
                [$name]
            );
        }

        return $result['count'] > 0;
    }

    /**
     * Get system roles (non-deletable)
     */
    public function getSystemRoles(): array
    {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE is_system = 1 ORDER BY name"
        );
    }

    /**
     * Get custom roles (user-created)
     */
    public function getCustomRoles(): array
    {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE is_system = 0 ORDER BY name"
        );
    }
}
