<?php

/**
 * AnimaID API Router
 * Main entry point for all API requests
 */

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';

// Set headers for JSON API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the request path and method
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string and decode URL
$path = parse_url($requestUri, PHP_URL_PATH);
$path = str_replace('/api/', '', $path); // Remove /api/ prefix
$path = trim($path, '/');

// Split path into segments
$pathSegments = explode('/', $path);
$endpoint = $pathSegments[0] ?? '';
$resourceId = $pathSegments[1] ?? null;

// Get request body for POST/PUT requests
$requestBody = json_decode(file_get_contents('php://input'), true) ?? [];

// Get authorization header
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = null;
if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $token = $matches[1];
}

try {
    $auth = new Auth();

    // Route the request
    $response = handleRequest($endpoint, $resourceId, $requestMethod, $requestBody, $token, $auth);

    // Send successful response
    http_response_code($response['status'] ?? 200);
    echo json_encode([
        'success' => true,
        ...$response
    ]);

} catch (Exception $e) {
    // Send error response
    $statusCode = 500;
    $errorMessage = 'Internal server error';

    if (strpos($e->getMessage(), 'Invalid credentials') !== false) {
        $statusCode = 401;
        $errorMessage = 'Invalid credentials';
    } elseif (strpos($e->getMessage(), 'Invalid token') !== false) {
        $statusCode = 401;
        $errorMessage = 'Authentication required';
    } elseif (strpos($e->getMessage(), 'Insufficient permissions') !== false) {
        $statusCode = 403;
        $errorMessage = 'Insufficient permissions';
    } elseif (strpos($e->getMessage(), 'not found') !== false) {
        $statusCode = 404;
        $errorMessage = 'Resource not found';
    }

    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'error' => $errorMessage
    ]);
}

function handleRequest(string $endpoint, ?string $resourceId, string $method, array $body, ?string $token, Auth $auth): array {
    switch ($endpoint) {
        case 'auth':
            return handleAuthRequest($resourceId, $method, $body, $token, $auth);

        case 'users':
            return handleUsersRequest($resourceId, $method, $body, $token, $auth);

        case 'roles':
            return handleRolesRequest($resourceId, $method, $body, $token, $auth);

        case 'permissions':
            return handlePermissionsRequest($method, $token, $auth);

        case 'system':
            return handleSystemRequest($resourceId, $method, $token, $auth);

        default:
            throw new Exception('Endpoint not found');
    }
}

function handleAuthRequest(?string $action, string $method, array $body, ?string $token, Auth $auth): array {
    switch ($action) {
        case 'login':
            if ($method !== 'POST') throw new Exception('Method not allowed');

            $username = $body['username'] ?? '';
            $password = $body['password'] ?? '';

            if (empty($username) || empty($password)) {
                throw new Exception('Username and password are required');
            }

            return $auth->login($username, $password);

        case 'logout':
            if ($method !== 'POST') throw new Exception('Method not allowed');
            if (!$token) throw new Exception('Authentication required');

            $auth->logout($token);
            return ['message' => 'Logged out successfully'];

        case 'me':
            if ($method !== 'GET') throw new Exception('Method not allowed');
            if (!$token) throw new Exception('Authentication required');

            $user = $auth->verifyToken($token);
            return ['user' => $user];

        default:
            throw new Exception('Auth action not found');
    }
}

function handleUsersRequest(?string $userId, string $method, array $body, ?string $token, Auth $auth): array {
    if (!$token) throw new Exception('Authentication required');

    // Verify user has admin permissions
    $user = $auth->verifyToken($token);
    if (!$auth->checkPermission($user['id'], 'admin.users')) {
        throw new Exception('Insufficient permissions');
    }

    if ($userId === null) {
        // Handle collection requests
        switch ($method) {
            case 'GET':
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 20);
                $search = $_GET['search'] ?? '';

                return $auth->getUsers($page, $limit, $search);

            case 'POST':
                $username = $body['username'] ?? '';
                $email = $body['email'] ?? '';
                $password = $body['password'] ?? '';
                $roleIds = $body['role_ids'] ?? [];

                if (empty($username) || empty($email) || empty($password)) {
                    throw new Exception('Username, email, and password are required');
                }

                $userId = $auth->createUser($username, $email, $password, $roleIds);
                return [
                    'user_id' => $userId,
                    'message' => 'User created successfully'
                ];

            default:
                throw new Exception('Method not allowed');
        }
    } else {
        // Handle individual user requests
        $userId = (int)$userId;

        switch ($method) {
            case 'GET':
                $users = $auth->getUsers(1, 1)['users'];
                if (empty($users)) {
                    throw new Exception('User not found');
                }

                $user = $users[0];
                $user['roles'] = $auth->getUserRoles($userId);
                return ['user' => $user];

            case 'PUT':
                $auth->updateUser($userId, $body);
                return ['message' => 'User updated successfully'];

            case 'DELETE':
                $auth->updateUser($userId, ['is_active' => false]);
                return ['message' => 'User deactivated successfully'];

            default:
                throw new Exception('Method not allowed');
        }
    }
}

function handleRolesRequest(?string $roleId, string $method, array $body, ?string $token, Auth $auth): array {
    if (!$token) throw new Exception('Authentication required');

    $user = $auth->verifyToken($token);
    if (!$auth->checkPermission($user['id'], 'admin.roles')) {
        throw new Exception('Insufficient permissions');
    }

    if ($roleId === null) {
        // Handle collection requests
        switch ($method) {
            case 'GET':
                return ['roles' => $auth->getRoles()];

            case 'POST':
                $name = $body['name'] ?? '';
                $displayName = $body['display_name'] ?? '';
                $permissionIds = $body['permission_ids'] ?? [];

                if (empty($name) || empty($displayName)) {
                    throw new Exception('Role name and display name are required');
                }

                $roleId = $auth->createRole($name, $displayName, $permissionIds);
                return [
                    'role_id' => $roleId,
                    'message' => 'Role created successfully'
                ];

            default:
                throw new Exception('Method not allowed');
        }
    } else {
        // Handle individual role requests
        $roleId = (int)$roleId;

        switch ($method) {
            case 'GET':
                $roles = $auth->getRoles();
                $role = array_filter($roles, fn($r) => $r['id'] == $roleId);
                if (empty($role)) {
                    throw new Exception('Role not found');
                }
                return ['role' => array_values($role)[0]];

            case 'PUT':
                $auth->updateRole($roleId, $body);
                return ['message' => 'Role updated successfully'];

            case 'DELETE':
                $auth->deleteRole($roleId);
                return ['message' => 'Role deleted successfully'];

            default:
                throw new Exception('Method not allowed');
        }
    }
}

function handlePermissionsRequest(string $method, ?string $token, Auth $auth): array {
    if (!$token) throw new Exception('Authentication required');

    $user = $auth->verifyToken($token);
    if (!$auth->checkPermission($user['id'], 'admin.roles')) {
        throw new Exception('Insufficient permissions');
    }

    switch ($method) {
        case 'GET':
            return ['permissions' => $auth->getPermissions()];

        default:
            throw new Exception('Method not allowed');
    }
}

function handleSystemRequest(?string $action, string $method, ?string $token, Auth $auth): array {
    if (!$token) throw new Exception('Authentication required');

    $user = $auth->verifyToken($token);
    if (!$auth->checkPermission($user['id'], 'admin.system')) {
        throw new Exception('Insufficient permissions');
    }

    switch ($action) {
        case 'status':
            if ($method !== 'GET') throw new Exception('Method not allowed');

            try {
                $config = require __DIR__ . '/../config.php';

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
