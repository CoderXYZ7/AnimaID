<?php

// Check user permissions script
require_once 'config.php';
require_once 'src/Database.php';
require_once 'src/Auth.php';

echo "Checking user permissions...\n";

try {
    $auth = new Auth();

    // Get all users
    $users = $auth->getUsers(1, 10, '')['users'];

    echo "Users in system:\n";
    foreach ($users as $user) {
        echo "- ID: {$user['id']}, Username: {$user['username']}, Roles: " . implode(', ', $user['roles']) . "\n";
    }

    // Check permissions for each user
    foreach ($users as $user) {
        echo "\nPermissions for user {$user['username']} (ID: {$user['id']}):\n";

        $testPermissions = [
            'admin.users',
            'admin.roles',
            'communications.view',
            'communications.send',
            'communications.manage',
            'calendar.view',
            'calendar.create',
            'registrations.view',
            'attendance.view',
            'spaces.view'
        ];

        foreach ($testPermissions as $permission) {
            $hasPermission = $auth->checkPermission($user['id'], $permission);
            echo "  {$permission}: " . ($hasPermission ? '✓' : '✗') . "\n";
        }
    }

    // Check all roles and their permissions
    echo "\nAll roles and permissions:\n";
    $roles = $auth->getRoles();
    foreach ($roles as $role) {
        echo "- {$role['display_name']} ({$role['name']}):\n";
        if (isset($role['permissions']) && is_array($role['permissions'])) {
            foreach ($role['permissions'] as $perm) {
                echo "  - {$perm}\n";
            }
        } else {
            echo "  - No permissions assigned\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
