<?php

namespace AnimaID\Services;

use AnimaID\Repositories\RoleRepository;
use AnimaID\Config\ConfigManager;
use PDO;

/**
 * Role Service
 * Handles role management and assignment operations
 */
class RoleService
{
    private RoleRepository $roleRepository;
    private ConfigManager $config;
    private PDO $db;

    public function __construct(
        RoleRepository $roleRepository,
        ConfigManager $config,
        PDO $db
    ) {
        $this->roleRepository = $roleRepository;
        $this->config = $config;
        $this->db = $db;
    }

    /**
     * Get all roles
     */
    public function getAllRoles(): array
    {
        return $this->roleRepository->findAll();
    }

    /**
     * Get all roles with permissions
     */
    public function getAllRolesWithPermissions(): array
    {
        return $this->roleRepository->getAllWithPermissions();
    }

    /**
     * Get role by ID
     */
    public function getRoleById(int $roleId): ?array
    {
        return $this->roleRepository->findWithPermissions($roleId);
    }

    /**
     * Create a new role
     */
    public function createRole(array $data): array
    {
        // Validate required fields
        if (empty($data['name'])) {
            throw new \Exception('Role name is required');
        }

        if (empty($data['display_name'])) {
            throw new \Exception('Display name is required');
        }

        // Check if name exists
        if ($this->roleRepository->nameExists($data['name'])) {
            throw new \Exception('Role name already exists');
        }

        // Prepare role data
        $roleData = [
            'name' => $data['name'],
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'is_system' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Insert role
        $roleId = $this->roleRepository->insert($roleData);

        // Assign permissions if provided
        if (!empty($data['permission_ids'])) {
            $this->roleRepository->assignPermissions($roleId, $data['permission_ids']);
        }

        return $this->getRoleById($roleId);
    }

    /**
     * Update role
     */
    public function updateRole(int $roleId, array $data): array
    {
        // Check if role exists
        $role = $this->roleRepository->findById($roleId);
        if (!$role) {
            throw new \Exception('Role not found');
        }

        // Prevent modification of system roles
        if ($role['is_system']) {
            throw new \Exception('Cannot modify system roles');
        }

        // Check name uniqueness if changed
        if (isset($data['name']) && $data['name'] !== $role['name']) {
            if ($this->roleRepository->nameExists($data['name'], $roleId)) {
                throw new \Exception('Role name already exists');
            }
        }

        // Prepare update data
        $updateData = [];
        $allowedFields = ['name', 'display_name', 'description'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        // Update role
        $this->roleRepository->update($roleId, $updateData);

        // Update permissions if provided
        if (isset($data['permission_ids'])) {
            $this->roleRepository->assignPermissions($roleId, $data['permission_ids']);
        }

        return $this->getRoleById($roleId);
    }

    /**
     * Delete role
     */
    public function deleteRole(int $roleId): bool
    {
        // Check if role exists
        $role = $this->roleRepository->findById($roleId);
        if (!$role) {
            throw new \Exception('Role not found');
        }

        // Prevent deletion of system roles
        if ($role['is_system']) {
            throw new \Exception('Cannot delete system roles');
        }

        return $this->roleRepository->delete($roleId);
    }

    /**
     * Assign role to user
     */
    public function assignRoleToUser(int $roleId, int $userId, int $assignedBy): bool
    {
        // Check if role exists
        if (!$this->roleRepository->exists($roleId)) {
            throw new \Exception('Role not found');
        }

        return $this->roleRepository->assignToUser($roleId, $userId, $assignedBy);
    }

    /**
     * Remove role from user
     */
    public function removeRoleFromUser(int $roleId, int $userId): bool
    {
        return $this->roleRepository->removeFromUser($roleId, $userId);
    }

    /**
     * Assign multiple roles to user
     */
    public function assignRolesToUser(int $userId, array $roleIds, int $assignedBy): bool
    {
        foreach ($roleIds as $roleId) {
            $this->assignRoleToUser($roleId, $userId, $assignedBy);
        }

        return true;
    }

    /**
     * Get users with a specific role
     */
    public function getUsersWithRole(int $roleId): array
    {
        return $this->roleRepository->getUsers($roleId);
    }

    /**
     * Get system roles
     */
    public function getSystemRoles(): array
    {
        return $this->roleRepository->getSystemRoles();
    }

    /**
     * Get custom roles
     */
    public function getCustomRoles(): array
    {
        return $this->roleRepository->getCustomRoles();
    }
}
