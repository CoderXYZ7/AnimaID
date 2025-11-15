<?php

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

try {
    $auth = new Auth();

    // Test login to get token
    echo "Testing login...\n";
    $loginResult = $auth->login('admin', 'Admin123!@#');
    $token = $loginResult['token'];
    echo "Login successful, token: " . substr($token, 0, 50) . "...\n";

    // Test token verification
    echo "Testing token verification...\n";
    $user = $auth->verifyToken($token);
    echo "Token verification successful, user ID: " . $user['id'] . "\n";

    // Test createWikiPage via API simulation
    echo "Testing createWikiPage via API simulation...\n";

    // Simulate the API request data
    $pageData = [
        'title' => 'API Test Wiki Page',
        'content' => 'This is a test wiki page created via API.',
        'summary' => 'API test summary',
        'is_published' => 1,
        'is_featured' => 0
    ];

    $pageId = $auth->createWikiPage($pageData, $user['id']);
    echo "API simulation successful! Created page with ID: $pageId\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
