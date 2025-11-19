<?php

// Test the status page functionality
echo "Testing status page functionality...\n";

// Simulate what the status page does
$apiBaseUrl = 'http://localhost:8000/api';

// Test 1: Check if API is accessible
echo "Test 1: Checking API accessibility...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . '/system/status');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJBbmltYUlEIiwiYXVkIjoiQW5pbWFJRCIsImlhdCI6MTc2MzA2Njg5MiwiZXhwIjoxNzYzMDc0MDkyLCJ1c2VyX2lkIjoxLCJ1c2VybmFtZSI6ImFkbWluIiwicm9sZXMiOlsidGVjaG5pY2FsX2FkbWluIl19.UlxHakAVF00sMuVGUCcaiVMbu0KJyvc74CU9iz83MlY'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "API Response Code: $httpCode\n";
echo "API Response: $response\n";

if ($httpCode === 200) {
    echo "✓ API is working\n";
} else {
    echo "✗ API is not working\n";
}

// Test 2: Check if we can parse the JSON
$data = json_decode($response, true);
if ($data && isset($data['status'])) {
    echo "✓ JSON parsing works\n";
    echo "System status: " . $data['status'] . "\n";
} else {
    echo "✗ JSON parsing failed\n";
}

// Test 3: Check other API endpoints used by status page
echo "\nTest 3: Checking other endpoints...\n";

$endpoints = [
    '/users?page=1&limit=1',
    '/roles',
    '/permissions'
];

foreach ($endpoints as $endpoint) {
    echo "Testing $endpoint...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJBbmltYUlEIiwiYXVkIjoiQW5pbWFJRCIsImlhdCI6MTc2MzA2Njg5MiwiZXhwIjoxNzYzMDc0MDkyLCJ1c2VyX2lkIjoxLCJ1c2VybmFtZSI6ImFkbWluIiwicm9sZXMiOlsidGVjaG5pY2FsX2FkbWluIl19.UlxHakAVF00sMuVGUCcaiVMbu0KJyvc74CU9iz83MlY'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        echo "✓ $endpoint works\n";
    } else {
        echo "✗ $endpoint failed with code $httpCode\n";
    }
}

echo "\nStatus page functionality test completed.\n";
