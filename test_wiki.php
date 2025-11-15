<?php

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

try {
    $auth = new Auth();

    // Test data
    $pageData = [
        'title' => 'Test Wiki Page',
        'content' => 'This is a test wiki page content.',
        'summary' => 'Test summary',
        'is_published' => 1,
        'is_featured' => 0
    ];

    $userId = 1; // Admin user

    echo "Testing createWikiPage...\n";
    $pageId = $auth->createWikiPage($pageData, $userId);
    echo "Success! Created page with ID: $pageId\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
