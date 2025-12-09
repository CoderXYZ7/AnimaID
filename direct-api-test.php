<?php
/**
 * Direct API test - simulates what happens when /api/calendar is requested
 */

// Simulate the request
$_SERVER['REQUEST_URI'] = '/api/calendar?status=published';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Set a fake token for testing (you'll need to replace this with a real one)
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test-token';

echo "<!-- Starting API test -->\n";
echo "<!-- Request URI: " . $_SERVER['REQUEST_URI'] . " -->\n";
echo "<!-- Including api/index.php -->\n";

// Include the API
require_once __DIR__ . '/api/index.php';
