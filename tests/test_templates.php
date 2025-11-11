<?php

// Test API animators templates endpoint
require_once 'config.php';
require_once 'src/Database.php';
require_once 'src/Auth.php';

echo "Testing API animators templates endpoint...\n";

try {
    $auth = new Auth();

    // Test the getAvailabilityTemplates method directly
    $templates = $auth->getAvailabilityTemplates();

    echo "Direct method test - Templates found: " . count($templates) . "\n";

    if (count($templates) > 0) {
        foreach ($templates as $template) {
            echo "- {$template['name']}: {$template['description']} (ID: {$template['id']})\n";
        }
        echo "\nDirect method test PASSED!\n";
    } else {
        echo "No templates found. This might be expected if database wasn't initialized.\n";
        echo "Run 'php database/init.php' to initialize the database.\n";
    }

    // Test via HTTP
    echo "\nTesting via HTTP...\n";

    // Login as admin to get a valid token
    $loginResult = $auth->login('admin', 'Admin123!@#');
    $token = $loginResult['token'];

    echo "Got token for admin\n";

    // Test the templates endpoint via HTTP
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Authorization: Bearer ' . $token . "\r\n" .
                       'Content-Type: application/json' . "\r\n"
        ]
    ]);

    $url = 'http://localhost:8000/api/animators/templates';
    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        echo "HTTP test FAILED: Could not connect to server\n";
    } else {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "HTTP test PASSED: Templates endpoint working via HTTP!\n";
            echo "Templates via HTTP: " . count($data['templates']) . "\n";
        } else {
            echo "HTTP test FAILED: Invalid response\n";
            echo "Response: " . $response . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
