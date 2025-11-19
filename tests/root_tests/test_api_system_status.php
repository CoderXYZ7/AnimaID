<?php

require_once 'src/Database.php';
require_once 'src/Auth.php';

$auth = new Auth();

// Test the API endpoint directly
try {
    echo "Testing API system status endpoint directly...\n";

    // Login first
    $loginResult = $auth->login('admin', 'Admin123!@#');
    if (!isset($loginResult['token'])) {
        throw new Exception('Login failed');
    }

    $token = $loginResult['token'];
    echo "Got token: " . substr($token, 0, 20) . "...\n";

    // Simulate the API request
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/system/status';
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

    // Include the API handler
    echo "Including API handler...\n";
    ob_start();
    include 'api/index.php';
    $output = ob_get_clean();

    echo "API Response:\n";
    echo $output . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
