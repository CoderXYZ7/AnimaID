<?php

require_once 'src/Database.php';
require_once 'src/Auth.php';

$auth = new Auth();

try {
    echo "Testing system status API...\n";

    // Simulate the system status API call
    $token = null; // We'll skip token validation for this test

    // Get database stats
    $db = Database::getInstance();

    echo "Getting user count...\n";
    $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
    echo "Users: $userCount\n";

    echo "Getting role count...\n";
    $roleCount = $db->fetchOne("SELECT COUNT(*) as count FROM roles")['count'];
    echo "Roles: $roleCount\n";

    echo "Getting permission count...\n";
    $permissionCount = $db->fetchOne("SELECT COUNT(*) as count FROM permissions")['count'];
    echo "Permissions: $permissionCount\n";

    echo "Loading config...\n";
    $config = require __DIR__ . '/config/config.php';
    echo "Config loaded successfully\n";
    echo "Version: " . ($config['system']['version'] ?? 'unknown') . "\n";

    echo "System status test completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
