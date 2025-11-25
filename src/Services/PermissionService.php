<?php

namespace AnimaID\Services;

use AnimaID\Repositories\PermissionRepository;
use AnimaID\Config\ConfigManager;
use PDO;

/**
 * Permission Service
 * Handles permission checking and validation
 */
class PermissionService
{
    private PermissionRepository $permissionRepository;
    private ConfigManager $config;
    private PDO $db;

    public function __construct(
        PermissionRepository $permissionRepository,
        ConfigManager $config,
        PDO $db
    ) {
        $this->permissionRepository = $permissionRepository;
        $this->config = $config;
        $this->db = $db;
    }

    /**
     * Get all permissions
     */
    public function getAllPermissions(): array
    {
        return $this->permissionRepository->findAll();
    }

    /**
     * Get all permissions grouped by category
     */
    public function getAllPermissionsGrouped(): array
    {
        return $this->permissionRepository->getAllGroupedByCategory();
    }

    /**
     * Get permissions for a user
     */
    public function getUserPermissions(int $userId): array
    {
        return $this->permissionRepository->getForUser($userId);
    }

    /**
     * Get permissions for a user grouped by category
     */
    public function getUserPermissionsGrouped(int $userId): array
    {
        return $this->permissionRepository->getForUserGrouped($userId);
    }

    /**
     * Check if user has permission
     */
    public function checkPermission(int $userId, string $permission): bool
    {
        return $this->permissionRepository->userHasPermission($userId, $permission);
    }

    /**
     * Check if user has any of the permissions
     */
    public function checkAnyPermission(int $userId, array $permissions): bool
    {
        return $this->permissionRepository->userHasAnyPermission($userId, $permissions);
    }

    /**
     * Check if user has all of the permissions
     */
    public function checkAllPermissions(int $userId, array $permissions): bool
    {
        return $this->permissionRepository->userHasAllPermissions($userId, $permissions);
    }

    /**
     * Require permission or throw exception
     */
    public function requirePermission(int $userId, string $permission): void
    {
        if (!$this->checkPermission($userId, $permission)) {
            throw new \Exception('Permission denied: ' . $permission);
        }
    }

    /**
     * Require any of the permissions or throw exception
     */
    public function requireAnyPermission(int $userId, array $permissions): void
    {
        if (!$this->checkAnyPermission($userId, $permissions)) {
            throw new \Exception('Permission denied: requires one of ' . implode(', ', $permissions));
        }
    }

    /**
     * Require all permissions or throw exception
     */
    public function requireAllPermissions(int $userId, array $permissions): void
    {
        if (!$this->checkAllPermissions($userId, $permissions)) {
            throw new \Exception('Permission denied: requires all of ' . implode(', ', $permissions));
        }
    }

    /**
     * Check if user is admin (has admin.* permissions)
     */
    public function isAdmin(int $userId): bool
    {
        $permissions = $this->getUserPermissions($userId);
        
        foreach ($permissions as $permission) {
            if (str_starts_with($permission['name'], 'admin.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user is technical admin
     */
    public function isTechnicalAdmin(int $userId): bool
    {
        return $this->checkPermission($userId, 'admin.technical');
    }

    /**
     * Get permission categories
     */
    public function getCategories(): array
    {
        return $this->permissionRepository->getCategories();
    }

    /**
     * Get permissions by category
     */
    public function getByCategory(string $category): array
    {
        return $this->permissionRepository->getByCategory($category);
    }
}
