<?php

require_once 'src/Database.php';
require_once 'src/Auth.php';

$auth = new Auth();

try {
    echo "Testing login...\n";
    $user = $auth->login('admin', 'Admin123!@#');
    echo "Login successful for user: " . $user['user']['username'] . "\n";

    echo "Testing permission check...\n";
    $hasPermission = $auth->checkPermission(1, 'admin.system.view');
    echo "Has admin.system.view permission: " . ($hasPermission ? 'yes' : 'no') . "\n";

    echo "Testing isAdmin check...\n";
    $isAdmin = $auth->isAdmin(1);
    echo "Is admin: " . ($isAdmin ? 'yes' : 'no') . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
