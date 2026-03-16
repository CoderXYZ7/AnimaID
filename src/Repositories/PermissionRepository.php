<?php

namespace AnimaID\Repositories;

use PDO;

/**
 * Permission Repository
 * Handles all permission data access operations
 */
class PermissionRepository extends BaseRepository
{
    protected string $table = 'permissions';

    /**
     * Find permission by name
     */
    public function findByName(string $name): ?array
    {
        return $this->queryOne(
            "SELECT * FROM {$this->table} WHERE name = ?",
            [$name]
        );
    }

    /**
     * Get all permissions grouped by module
     */
    public function getAllGroupedByModule(): array
    {
        $permissions = $this->query(
            "SELECT * FROM {$this->table} ORDER BY module, name"
        );

        $grouped = [];
        foreach ($permissions as $permission) {
            $module = $permission['module'];
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $permission;
        }

        return $grouped;
    }

    /**
     * Get permissions by module
     */
    public function getByCategory(string $module): array
    {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE module = ? ORDER BY name",
            [$module]
        );
    }

    /**
     * Get all categories
     */
    public function getCategories(): array
    {
        $result = $this->query(
            "SELECT DISTINCT module FROM {$this->table} ORDER BY module"
        );

        return array_column($result, 'module');
    }

    /**
     * Get permissions for a user (through roles)
     */
    public function getForUser(int $userId): array
    {
        return $this->query(
            "SELECT DISTINCT p.* FROM {$this->table} p
             INNER JOIN role_permissions rp ON p.id = rp.permission_id
             INNER JOIN user_roles ur ON rp.role_id = ur.role_id
             WHERE ur.user_id = ?
             ORDER BY p.module, p.name",
            [$userId]
        );
    }

    /**
     * Get permissions for a user grouped by module
     */
    public function getForUserGrouped(int $userId): array
    {
        $permissions = $this->getForUser($userId);

        $grouped = [];
        foreach ($permissions as $permission) {
            $module = $permission['module'];
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $permission;
        }

        return $grouped;
    }

    /**
     * Check if user has permission
     */
    public function userHasPermission(int $userId, string $permissionName): bool
    {
        $result = $this->queryOne(
            "SELECT COUNT(*) as count FROM {$this->table} p
             INNER JOIN role_permissions rp ON p.id = rp.permission_id
             INNER JOIN user_roles ur ON rp.role_id = ur.role_id
             WHERE ur.user_id = ? AND p.name = ?",
            [$userId, $permissionName]
        );

        return $result['count'] > 0;
    }

    /**
     * Check if user has any of the permissions
     */
    public function userHasAnyPermission(int $userId, array $permissionNames): bool
    {
        $placeholders = implode(',', array_fill(0, count($permissionNames), '?'));
        $params = array_merge([$userId], $permissionNames);

        $result = $this->queryOne(
            "SELECT COUNT(*) as count FROM {$this->table} p
             INNER JOIN role_permissions rp ON p.id = rp.permission_id
             INNER JOIN user_roles ur ON rp.role_id = ur.role_id
             WHERE ur.user_id = ? AND p.name IN ({$placeholders})",
            $params
        );

        return $result['count'] > 0;
    }

    /**
     * Check if user has all of the permissions
     */
    public function userHasAllPermissions(int $userId, array $permissionNames): bool
    {
        $placeholders = implode(',', array_fill(0, count($permissionNames), '?'));
        $params = array_merge([$userId], $permissionNames);

        $result = $this->queryOne(
            "SELECT COUNT(DISTINCT p.name) as count FROM {$this->table} p
             INNER JOIN role_permissions rp ON p.id = rp.permission_id
             INNER JOIN user_roles ur ON rp.role_id = ur.role_id
             WHERE ur.user_id = ? AND p.name IN ({$placeholders})",
            $params
        );

        return $result['count'] == count($permissionNames);
    }

    /**
     * Check if permission name exists
     */
    public function nameExists(string $name, ?int $excludePermissionId = null): bool
    {
        if ($excludePermissionId) {
            $result = $this->queryOne(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE name = ? AND id != ?",
                [$name, $excludePermissionId]
            );
        } else {
            $result = $this->queryOne(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE name = ?",
                [$name]
            );
        }

        return $result['count'] > 0;
    }
}
