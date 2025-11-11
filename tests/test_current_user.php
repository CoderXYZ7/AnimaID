<?php

// Test current user authentication
require_once 'config.php';
require_once 'src/Database.php';
require_once 'src/Auth.php';

echo "Testing current user authentication...\n";

try {
    $auth = new Auth();

    // Test with known users
    $testUsers = [
        'admin' => 'Admin123!@#',
        'testuser' => 'TestPass123!'
    ];

    // First, let's see what users exist
    echo "Existing users in database:\n";
    $users = $auth->getUsers(1, 10, '')['users'];
    foreach ($users as $user) {
        echo "- ID: {$user['id']}, Username: {$user['username']}, Roles: " . implode(', ', $user['roles']) . "\n";
    }
    echo "\n";

    foreach ($testUsers as $username => $password) {
        echo "\nTesting login for: $username\n";

        try {
            $result = $auth->login($username, $password);
            echo "Login successful for $username\n";
            echo "Token: " . substr($result['token'], 0, 50) . "...\n";

            // Verify the token
            $user = $auth->verifyToken($result['token']);
            echo "Verified user: " . $user['username'] . " (ID: " . $user['id'] . ")\n";
            echo "User roles: " . implode(', ', $user['roles']) . "\n";

            // Test communications permission
            $hasPermission = $auth->checkPermission($user['id'], 'communications.view');
            echo "Has communications.view permission: " . ($hasPermission ? 'YES' : 'NO') . "\n";

        } catch (Exception $e) {
            echo "Login failed for $username: " . $e->getMessage() . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
