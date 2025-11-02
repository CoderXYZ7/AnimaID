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

    // More specific error handling
    $errorMsg = $e->getMessage();
    if (strpos($errorMsg, 'Invalid credentials') !== false) {
        $statusCode = 401;
        $errorMessage = 'Invalid credentials';
    } elseif (strpos($errorMsg, 'Invalid token') !== false || strpos($errorMsg, 'Expired token') !== false) {
        $statusCode = 401;
        $errorMessage = 'Authentication token expired or invalid. Please log in again.';
    } elseif (strpos($errorMsg, 'Insufficient permissions') !== false) {
        $statusCode = 403;
        $errorMessage = 'Insufficient permissions';
    } elseif (strpos($errorMsg, 'not found') !== false) {
        $statusCode = 404;
        $errorMessage = 'Resource not found';
    } elseif (strpos($errorMsg, 'Authentication required') !== false) {
        $statusCode = 401;
        $errorMessage = 'Authentication required';
    }

    // Log detailed error for debugging (only in development)
    error_log("API Error [{$endpoint}]: " . $errorMsg);
    error_log("API Error Details - Method: {$requestMethod}, Endpoint: {$endpoint}, UserId: {$resourceId}");
    error_log("API Error Request Body: " . json_encode($requestBody));
    error_log("API Error Stack Trace: " . $e->getTraceAsString());

    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'error' => $errorMessage,
        'endpoint' => $endpoint,
        'method' => $requestMethod,
        'debug_info' => [
            'error_message' => $errorMsg,
            'endpoint' => $endpoint,
            'method' => $requestMethod,
            'user_id' => $resourceId
        ]
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

        case 'calendar':
            return handleCalendarRequest($resourceId, $method, $body, $token, $auth);

        case 'attendance':
            return handleAttendanceRequest($resourceId, $method, $body, $token, $auth);

        case 'children':
            return handleChildrenRequest($resourceId, $method, $body, $token, $auth);

        case 'spaces':
            return handleSpacesRequest($resourceId, $method, $body, $token, $auth);

        case 'system':
            return handleSystemRequest($resourceId, $method, $token, $auth);

        case 'communications':
            return handleCommunicationsRequest($resourceId, $method, $body, $token, $auth);

        case 'media':
            return handleMediaRequest($resourceId, $method, $body, $token, $auth);

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

function handleCommunicationsRequest(?string $communicationId, string $method, array $body, ?string $token, Auth $auth): array {
    global $pathSegments;

    $user = null;

    // For public communications, token is optional
    if ($token) {
        $user = $auth->verifyToken($token);
    }

    // Check if this is a comments endpoint: /api/communications/{id}/comments
    if ($communicationId && isset($pathSegments[2]) && $pathSegments[2] === 'comments') {
        $communicationId = (int)$communicationId;

        switch ($method) {
            case 'GET':
                // Get comments for a communication
                $comments = $auth->getCommunicationComments($communicationId);
                return ['comments' => $comments];

            case 'POST':
                // Add comment to communication
                $commentData = $body;
                $commentData['communication_id'] = $communicationId;

                // For public comments, no authentication required
                if (!$user) {
                    $commentData['created_by'] = null; // Anonymous comment
                }

                $commentId = $auth->addCommunicationComment($communicationId, $commentData, $user['id'] ?? null);
                return [
                    'comment_id' => $commentId,
                    'message' => 'Comment added successfully'
                ];

            default:
                throw new Exception('Method not allowed');
        }
    }

    if ($communicationId === null) {
        // Handle collection requests
        switch ($method) {
            case 'GET':
                // Check if requesting public or internal communications
                $isPublic = isset($_GET['public']) ? (int)$_GET['public'] : 0;

                if ($isPublic) {
                    // Public communications - no auth required
                    $page = (int)($_GET['page'] ?? 1);
                    $limit = (int)($_GET['limit'] ?? 10);
                    return $auth->getCommunications($page, $limit, ['is_public' => 1, 'status' => 'published']);
                } else {
                    // Internal communications - auth required
                    if (!$user || !$auth->checkPermission($user['id'], 'communications.view')) {
                        throw new Exception('Authentication required or insufficient permissions');
                    }

                    $page = (int)($_GET['page'] ?? 1);
                    $limit = (int)($_GET['limit'] ?? 20);

                    $filters = [];
                    if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
                    if (isset($_GET['communication_type'])) $filters['communication_type'] = $_GET['communication_type'];
                    if (isset($_GET['priority'])) $filters['priority'] = $_GET['priority'];
                    if (isset($_GET['target_audience'])) $filters['target_audience'] = $_GET['target_audience'];

                    return $auth->getCommunications($page, $limit, $filters);
                }

            case 'POST':
                if (!$user || !$auth->checkPermission($user['id'], 'communications.send')) {
                    throw new Exception('Authentication required or insufficient permissions');
                }

                $communicationId = $auth->createCommunication($body, $user['id']);
                return [
                    'communication_id' => $communicationId,
                    'message' => 'Communication created successfully'
                ];

            default:
                throw new Exception('Method not allowed');
        }
    } else {
        // Handle individual communication requests
        $communicationId = (int)$communicationId;

        switch ($method) {
            case 'GET':
                $communication = $auth->getCommunicationDetails($communicationId);

                if (!$communication) {
                    throw new Exception('Communication not found');
                }

                // Check if public communication or user has permission to view
                if (!$communication['is_public'] && (!$user || !$auth->checkPermission($user['id'], 'communications.view'))) {
                    throw new Exception('Insufficient permissions');
                }

                // Record the view for analytics
                $auth->recordCommunicationView($communicationId, $user['id'] ?? null);

                return ['communication' => $communication];

            case 'PUT':
                if (!$user || !$auth->checkPermission($user['id'], 'communications.send')) {
                    throw new Exception('Authentication required or insufficient permissions');
                }

                $auth->updateCommunication($communicationId, $body);
                return ['message' => 'Communication updated successfully'];

            case 'DELETE':
                if (!$user || !$auth->checkPermission($user['id'], 'communications.manage')) {
                    throw new Exception('Authentication required or insufficient permissions');
                }

                $auth->deleteCommunication($communicationId);
                return ['message' => 'Communication deleted successfully'];

            default:
                throw new Exception('Method not allowed');
        }
    }
}

function handleChildrenRequest(?string $childId, string $method, array $body, ?string $token, Auth $auth): array {
    if (!$token) throw new Exception('Authentication required');

    $user = $auth->verifyToken($token);
    if (!$auth->checkPermission($user['id'], 'registrations.view')) {
        throw new Exception('Insufficient permissions');
    }

    if ($childId === null) {
        // Handle collection requests
        switch ($method) {
            case 'GET':
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 20);

                $filters = [];
                if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
                if (isset($_GET['search'])) $filters['search'] = $_GET['search'];
                if (isset($_GET['age_min'])) $filters['age_min'] = (int)$_GET['age_min'];
                if (isset($_GET['age_max'])) $filters['age_max'] = (int)$_GET['age_max'];

                return $auth->getChildren($page, $limit, $filters);

            case 'POST':
                if (!$auth->checkPermission($user['id'], 'registrations.create')) {
                    throw new Exception('Insufficient permissions');
                }

                $childId = $auth->createChild($body, $user['id']);
                return [
                    'child_id' => $childId,
                    'message' => 'Child registered successfully'
                ];

            default:
                throw new Exception('Method not allowed');
        }
    } else {
        // Handle individual child requests
        $childId = (int)$childId;

        switch ($method) {
            case 'GET':
                return ['child' => $auth->getChildDetails($childId)];

            case 'PUT':
                if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->updateChild($childId, $body, $user['id']);
                return ['message' => 'Child information updated successfully'];

            case 'DELETE':
                if (!$auth->checkPermission($user['id'], 'registrations.delete')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->deleteChild($childId);
                return ['message' => 'Child record deleted successfully'];

            default:
                throw new Exception('Method not allowed');
        }
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

function handleCalendarRequest(?string $eventId, string $method, array $body, ?string $token, Auth $auth): array {
    if (!$token) throw new Exception('Authentication required');

    $user = $auth->verifyToken($token);
    if (!$auth->checkPermission($user['id'], 'calendar.view')) {
        throw new Exception('Insufficient permissions');
    }

    if ($eventId === null) {
        // Handle collection requests
        switch ($method) {
            case 'GET':
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 20);

                $filters = [];
                if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
                if (isset($_GET['event_type'])) $filters['event_type'] = $_GET['event_type'];
                if (isset($_GET['start_date'])) $filters['start_date'] = $_GET['start_date'];
                if (isset($_GET['end_date'])) $filters['end_date'] = $_GET['end_date'];
                if (isset($_GET['is_public'])) $filters['is_public'] = (int)$_GET['is_public'];

                return $auth->getCalendarEvents($page, $limit, $filters);

            case 'POST':
                if (!$auth->checkPermission($user['id'], 'calendar.create')) {
                    throw new Exception('Insufficient permissions');
                }

                $eventId = $auth->createCalendarEvent($body, $user['id']);
                return [
                    'event_id' => $eventId,
                    'message' => 'Event created successfully'
                ];

            default:
                throw new Exception('Method not allowed');
        }
    } else {
        // Handle individual event requests
        $eventId = (int)$eventId;

        switch ($method) {
            case 'GET':
                $events = $auth->getCalendarEvents(1, 1)['events'];
                if (empty($events)) {
                    throw new Exception('Event not found');
                }

                $event = $events[0];
                $event['participants'] = $auth->getEventParticipants($eventId);
                return ['event' => $event];

            case 'PUT':
                if (!$auth->checkPermission($user['id'], 'calendar.edit')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->updateCalendarEvent($eventId, $body);
                return ['message' => 'Event updated successfully'];

            case 'DELETE':
                if (!$auth->checkPermission($user['id'], 'calendar.delete')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->deleteCalendarEvent($eventId);
                return ['message' => 'Event deleted successfully'];

            default:
                throw new Exception('Method not allowed');
        }
    }
}

function handleAttendanceRequest(?string $action, string $method, array $body, ?string $token, Auth $auth): array {
    if (!$token) throw new Exception('Authentication required');

    $user = $auth->verifyToken($token);

    // Handle individual attendance record operations (DELETE)
    if ($action !== null && $action !== 'checkin' && $action !== 'checkout') {
        $recordId = (int)$action;

        switch ($method) {
            case 'DELETE':
                if (!$auth->checkPermission($user['id'], 'attendance.edit')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->deleteAttendanceRecord($recordId);
                return ['message' => 'Attendance record deleted successfully'];

            default:
                throw new Exception('Method not allowed');
        }
    }

    // Handle action-based operations
    switch ($action) {
        case 'checkin':
            if ($method !== 'POST') throw new Exception('Method not allowed');
            if (!$auth->checkPermission($user['id'], 'attendance.checkin')) {
                throw new Exception('Insufficient permissions');
            }

            $childId = (int)($body['child_id'] ?? 0);
            $eventId = (int)($body['event_id'] ?? 0);
            $notes = $body['notes'] ?? '';

            if (!$childId || !$eventId) {
                throw new Exception('Child ID and Event ID are required');
            }

            $auth->checkInOutChild($childId, $eventId, 'checkin', $user['id'], $notes);
            return ['message' => 'Check-in recorded successfully'];

        case 'checkout':
            if ($method !== 'POST') throw new Exception('Method not allowed');
            if (!$auth->checkPermission($user['id'], 'attendance.checkin')) {
                throw new Exception('Insufficient permissions');
            }

            $childId = (int)($body['child_id'] ?? 0);
            $eventId = (int)($body['event_id'] ?? 0);
            $notes = $body['notes'] ?? '';

            if (!$childId || !$eventId) {
                throw new Exception('Child ID and Event ID are required');
            }

            $auth->checkInOutChild($childId, $eventId, 'checkout', $user['id'], $notes);
            return ['message' => 'Check-out recorded successfully'];

        case null:
            // Get attendance records
            if ($method !== 'GET') throw new Exception('Method not allowed');
            if (!$auth->checkPermission($user['id'], 'attendance.view')) {
                throw new Exception('Insufficient permissions');
            }

            $eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;
            $participantId = isset($_GET['participant_id']) ? (int)$_GET['participant_id'] : null;
            $date = $_GET['date'] ?? null;

            return ['records' => $auth->getAttendanceRecords($eventId, $participantId, $date)];

        default:
            throw new Exception('Attendance action not found');
    }
}

function handleSpacesRequest(?string $spaceId, string $method, array $body, ?string $token, Auth $auth): array {
    if (!$token) throw new Exception('Authentication required');

    $user = $auth->verifyToken($token);

    switch ($method) {
        case 'GET':
            if (!$auth->checkPermission($user['id'], 'spaces.view')) {
                throw new Exception('Insufficient permissions');
            }

            if ($spaceId === null) {
                return ['spaces' => $auth->getSpaces()];
            } else {
                // Get space bookings
                $startDate = $_GET['start_date'] ?? null;
                $endDate = $_GET['end_date'] ?? null;
                return ['bookings' => $auth->getSpaceBookings($startDate, $endDate)];
            }

        case 'POST':
            if (!$auth->checkPermission($user['id'], 'spaces.book')) {
                throw new Exception('Insufficient permissions');
            }

            $bookingId = $auth->createSpaceBooking($body, $user['id']);
            return [
                'booking_id' => $bookingId,
                'message' => 'Space booking created successfully'
            ];

        default:
            throw new Exception('Method not allowed');
    }
}
