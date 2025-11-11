<?php

/**
 * AnimaID Authentication Test Script
 * Tests the authentication system functionality
 */

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

$config = require __DIR__ . '/config.php';

echo "=== AnimaID Authentication System Test ===\n\n";

try {
    $auth = new Auth();

    // Test 1: Login with admin credentials
    echo "Test 1: Admin Login\n";
    $loginResult = $auth->login($config['default_admin']['username'], $config['default_admin']['password']);
    echo "✓ Login successful\n";
    echo "  Token: " . $loginResult['token'] . "\n";
    echo "  User: " . $loginResult['user']['username'] . "\n";
    echo "  Roles: " . implode(', ', array_column($loginResult['user']['roles'], 'display_name')) . "\n\n";

    $token = $loginResult['token'];

    // Test 2: Verify token
    echo "Test 2: Token Verification\n";
    $verifiedUser = $auth->verifyToken($token);
    echo "✓ Token verified\n";
    echo "  User ID: " . $verifiedUser['id'] . "\n";
    echo "  Username: " . $verifiedUser['username'] . "\n\n";

    // Test 3: Check permissions
    echo "Test 3: Permission Checking\n";
    $permissions = [
        'admin.users',
        'admin.system',
        'registrations.view',
        'invalid.permission'
    ];

    foreach ($permissions as $permission) {
        $hasPermission = $auth->checkPermission($verifiedUser['id'], $permission);
        echo "  {$permission}: " . ($hasPermission ? '✓' : '✗') . "\n";
    }
    echo "\n";

    // Test 4: Get users list
    echo "Test 4: Get Users List\n";
    $users = $auth->getUsers(1, 5);
    echo "✓ Retrieved " . count($users['users']) . " users\n";
    foreach ($users['users'] as $user) {
        echo "  - {$user['username']} ({$user['email']}) - Roles: " . implode(', ', $user['roles']) . "\n";
    }
    echo "\n";

    // Test 5: Get roles
    echo "Test 5: Get Roles\n";
    $roles = $auth->getRoles();
    echo "✓ Retrieved " . count($roles) . " roles\n";
    foreach ($roles as $role) {
        echo "  - {$role['display_name']} ({$role['name']}) - Permissions: " . count($role['permissions']) . "\n";
    }
    echo "\n";

    // Test 6: Get permissions
    echo "Test 6: Get Permissions\n";
    $permissions = $auth->getPermissions();
    $totalPermissions = 0;
    foreach ($permissions as $module => $perms) {
        echo "  {$module}: " . count($perms) . " permissions\n";
        $totalPermissions += count($perms);
    }
    echo "✓ Total permissions: {$totalPermissions}\n\n";

    // Test 7: Create a test user
    echo "Test 7: Create Test User\n";
    $testUserId = $auth->createUser('testuser', 'test@example.com', 'TestPass123!', [2]); // Assign organizzatore role
    echo "✓ Test user created with ID: {$testUserId}\n\n";

    // Test 8: Login with test user
    echo "Test 8: Test User Login\n";
    $testLogin = $auth->login('testuser', 'TestPass123!');
    echo "✓ Test user login successful\n";
    echo "  Roles: " . implode(', ', array_column($testLogin['user']['roles'], 'display_name')) . "\n\n";

    // Test 9: Test permissions for test user
    echo "Test 9: Test User Permissions\n";
    $testPermissions = [
        'admin.users', // Should fail
        'registrations.view', // Should succeed
        'calendar.view', // Should succeed
    ];

    foreach ($testPermissions as $permission) {
        $hasPermission = $auth->checkPermission($testUserId, $permission);
        echo "  {$permission}: " . ($hasPermission ? '✓' : '✗') . "\n";
    }
    echo "\n";

    // Test 10: Logout
    echo "Test 10: Logout\n";
    $auth->logout($token);
    echo "✓ Logout successful\n\n";

    // Test 11: Try to verify expired token
    echo "Test 11: Verify Expired Token\n";
    try {
        $auth->verifyToken($token);
        echo "✗ Token should have been invalid\n";
    } catch (Exception $e) {
        echo "✓ Token correctly invalidated: " . $e->getMessage() . "\n";
    }

    echo "\n=== All Tests Completed Successfully! ===\n";

} catch (Exception $e) {
    echo "✗ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
