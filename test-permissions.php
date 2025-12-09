<?php
/**
 * Test permissions - access at https://animaidsgn.mywire.org/test-permissions.php
 */

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

header('Content-Type: application/json');

try {
    // Simulate getting the user from token (we'll just pick the first admin user)
    $db = new Database();
    $user = $db->fetchOne("SELECT * FROM users WHERE username = 'admin'");
    
    if (!$user) {
        throw new Exception('Admin user not found');
    }
    
    $auth = new Auth();
    $hasPermission = $auth->checkPermission($user['id'], 'attendance.view');
    
    echo json_encode([
        'success' => true,
        'user_id' => $user['id'],
        'username' => $user['username'],
        'permission' => 'attendance.view',
        'has_permission' => $hasPermission
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
