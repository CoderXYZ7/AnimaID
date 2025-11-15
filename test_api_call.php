<?php

// Simulate the exact API call
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

// Set up the request like the API would
$requestUri = '/api/system/status';
$requestMethod = 'GET';

// Remove query string and decode URL
$path = parse_url($requestUri, PHP_URL_PATH);
$path = str_replace('/api/', '', $path); // Remove /api/ prefix
$path = trim($path, '/');

// Split path into segments
$pathSegments = explode('/', $path);
$endpoint = $pathSegments[0] ?? '';
$resourceId = $pathSegments[1] ?? null;

echo "Parsed request:\n";
echo "Endpoint: $endpoint\n";
echo "Resource ID: $resourceId\n";

$requestBody = [];
$token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJBbmltYUlEIiwiYXVkIjoiQW5pbWFJRCIsImlhdCI6MTc2MzA2NDc4OCwiZXhwIjoxNzYzMDcxOTg4LCJ1c2VyX2lkIjoxLCJ1c2VybmFtZSI6ImFkbWluIiwicm9sZXMiOlsidGVjaG5pY2FsX2FkbWluIl19.WUcJhlj8tPHlptaN5yY7deWcDMm_DCdZ8n7bFxrI1SU';

try {
    $auth = new Auth();
    echo "Auth instance created\n";

    // Route the request
    $response = handleRequest($endpoint, $resourceId, $requestMethod, $requestBody, $token, $auth);

    echo "Request handled successfully\n";
    echo "Response: " . json_encode($response) . "\n";

} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Throwable $t) {
    echo "Throwable caught: " . $t->getMessage() . "\n";
    echo "Stack trace:\n" . $t->getTraceAsString() . "\n";
}

// Include the API functions
function handleRequest(string $endpoint, ?string $resourceId, string $method, array $body, ?string $token, Auth $auth): array {
    switch ($endpoint) {
        case 'system':
            return handleSystemRequest($resourceId, $method, $token, $auth);
        default:
            throw new Exception('Endpoint not found');
    }
}

function handleSystemRequest(?string $action, string $method, ?string $token, Auth $auth): array {
    if (!$token) throw new Exception('Authentication required');

    $user = $auth->verifyToken($token);
    if (!$auth->checkPermission($user['id'], 'admin.system.view')) {
        throw new Exception('Insufficient permissions');
    }

    switch ($action) {
        case 'status':
            if ($method !== 'GET') throw new Exception('Method not allowed');

            try {
                $config = require __DIR__ . '/config/config.php';

                // Get some basic database stats
                $db = Database::getInstance();
                $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
                $roleCount = $db->fetchOne("SELECT COUNT(*) as count FROM roles")['count'];
                $permissionCount = $db->fetchOne("SELECT COUNT(*) as count FROM permissions")['count'];

                return [
                    'status' => 'healthy',
                    'version' => $config['system']['version'] ?? '1.0.0',
                    'database' => true,
                    'timestamp' => date('c'),
                    'stats' => [
                        'users' => $userCount,
                        'roles' => $roleCount,
                        'permissions' => $permissionCount
                    ]
                ];
            } catch (Exception $e) {
                return [
                    'status' => 'error',
                    'version' => '1.0.0',
                    'database' => false,
                    'timestamp' => date('c'),
                    'error' => $e->getMessage()
                ];
            }

        default:
            throw new Exception('System action not found');
    }
}
