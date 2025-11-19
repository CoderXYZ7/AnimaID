<?php

// Simulate API environment
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

try {
    echo "Creating Auth instance...\n";
    $auth = new Auth();
    echo "Auth instance created successfully\n";

    echo "Testing token verification...\n";
    $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJBbmltYUlEIiwiYXVkIjoiQW5pbWFJRCIsImlhdCI6MTc2MzA2NDc4OCwiZXhwIjoxNzYzMDcxOTg4LCJ1c2VyX2lkIjoxLCJ1c2VybmFtZSI6ImFkbWluIiwicm9sZXMiOlsidGVjaG5pY2FsX2FkbWluIl19.WUcJhlj8tPHlptaN5yY7deWcDMm_DCdZ8n7bFxrI1SU';
    $user = $auth->verifyToken($token);
    echo "Token verified successfully for user: " . $user['username'] . "\n";

    echo "Testing permission check...\n";
    $hasPermission = $auth->checkPermission($user['id'], 'admin.system.view');
    echo "Has permission: " . ($hasPermission ? 'yes' : 'no') . "\n";

    echo "API Auth test completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
