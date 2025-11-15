<?php

require_once 'src/Database.php';
require_once 'src/Auth.php';

$auth = new Auth();

try {
    echo "Testing system status authentication...\n";

    // Test login
    echo "Attempting login...\n";
    $loginResult = $auth->login('admin', 'Admin123!@#');
    echo "Login result: " . json_encode($loginResult) . "\n";

    if (isset($loginResult['token'])) {
        $token = $loginResult['token'];
        echo "Token obtained: " . substr($token, 0, 20) . "...\n";

        // Test token verification
        echo "Verifying token...\n";
        $user = $auth->verifyToken($token);
        echo "User from token: " . json_encode($user) . "\n";

        // Test permission check
        echo "Checking admin.system.view permission...\n";
        $hasPermission = $auth->checkPermission($user['id'], 'admin.system.view');
        echo "Has permission: " . ($hasPermission ? 'YES' : 'NO') . "\n";

        // Test the actual API call simulation
        echo "Simulating API call...\n";

        // This simulates what happens in handleSystemRequest
        if (!$token) {
            throw new Exception('Authentication required');
        }

        $user = $auth->verifyToken($token);
        if (!$auth->checkPermission($user['id'], 'admin.system.view')) {
            throw new Exception('Insufficient permissions');
        }

        echo "API call simulation successful!\n";

    } else {
        echo "Login failed!\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
