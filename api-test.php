<?php
/**
 * API test - access at https://animaidsgn.mywire.org/api-test.php
 */
header('Content-Type: text/plain');

echo "=== API Routing Test ===\n\n";

$requestUri = '/api/calendar';
$path = parse_url($requestUri, PHP_URL_PATH);

echo "1. Original path: $path\n";

$path = ltrim($path, '/');
echo "2. After ltrim: $path\n";

$check = strpos($path, 'api/');
echo "3. strpos result: " . var_export($check, true) . "\n";
echo "4. Check === 0: " . var_export($check === 0, true) . "\n";

echo "\n5. API file exists: " . var_export(file_exists(__DIR__ . '/api/index.php'), true) . "\n";
echo "6. API file path: " . __DIR__ . '/api/index.php' . "\n";

echo "\n7. Attempting to include API...\n";
if (strpos($path, 'api/') === 0) {
    echo "   Routing to API!\n";
    echo "   Query String: " . $_SERVER['QUERY_STRING'] . "\n";
    echo "   GET params: " . print_r($_GET, true) . "\n";
} else {
    echo "   NOT routing to API\n";
}
