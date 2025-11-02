<?php

// Test API communications endpoint
require_once 'config.php';
require_once 'src/Database.php';
require_once 'src/Auth.php';

echo "Testing API communications endpoint...\n";

try {
    $auth = new Auth();

    // Login as admin to get a valid token
    $loginResult = $auth->login('admin', 'Admin123!@#');
    $token = $loginResult['token'];

    echo "Got token for admin\n";

    // Test the communications endpoint directly
    $url = 'http://localhost/api/communications?page=1&limit=1';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    echo "API Response Code: $httpCode\n";
    echo "API Response: $response\n";

    // Test with testuser4 if we can find the password
    // For now, let's check what happens with the admin token

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
