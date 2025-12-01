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

// Early error logging
error_log("API Request started: " . $_SERVER['REQUEST_METHOD'] . " " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
error_log("PHP version: " . PHP_VERSION);
error_log("Current dir: " . __DIR__);

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
$rawInput = file_get_contents('php://input');
$requestBody = json_decode($rawInput, true);
if ($requestBody === null) {
    if (!empty($rawInput)) {
        // JSON parsing failed
        error_log("JSON parsing failed. Raw input: " . substr($rawInput, 0, 500));
    }
    $requestBody = [];
}

// Get authorization header using robust extraction
function getAuthToken(): ?string {
    // 1. Read all headers in a case-insensitive way
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    
    $authHeader = null;
    
    // Try direct index first
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    } else {
        // Case-insensitive search (some SAPIs normalize keys differently)
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'authorization') {
                $authHeader = $value;
                break;
            }
        }
    }
    
    // 2. Fallbacks via $_SERVER (for some Apache setups)
    if (!$authHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }
    if (!$authHeader && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    
    if (!$authHeader) {
        return null;
    }
    
    // 3. Expect "Bearer <token>"
    if (stripos($authHeader, 'Bearer ') === 0) {
        return trim(substr($authHeader, 7));
    }
    
    return null;
}

$token = getAuthToken();

// For GET requests, also check query parameter as fallback
if (!$token && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = $_GET['token'] ?? null;
}

try {
    // Log the request for debugging
    error_log("API Request: {$requestMethod} {$endpoint}/{$resourceId}");

    $auth = new Auth();

    // Route the request
    $response = handleRequest($endpoint, $resourceId, $requestMethod, $requestBody, $token, $auth);

    // Send successful response
    http_response_code(200);
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
    if (strpos($errorMsg, 'This user is already a primary user for another animator') !== false) {
        $statusCode = 409; // Conflict
        $errorMessage = $errorMsg;
    } elseif (strpos($errorMsg, 'Animator is already linked to this user') !== false) {
        $statusCode = 409; // Conflict
        $errorMessage = $errorMsg;
    } elseif (strpos($errorMsg, 'Invalid credentials') !== false) {
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
    error_log("API Error Extracted Token: " . ($token ? 'present' : 'null'));
    error_log("API Error GET Token: " . ($_GET['token'] ?? 'null'));
    error_log("API Error All Headers: " . json_encode(getallheaders()));

    // Get recent error logs for debugging
    $recentLogs = [];
    $logFile = __DIR__ . '/../logs/animaid.log';
    if (file_exists($logFile)) {
        $lines = array_slice(file($logFile), -10); // Get last 10 lines
        $recentLogs = array_map('trim', $lines);
    }

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
            'user_id' => $resourceId,
            'path_segments' => $pathSegments,
            'request_body_length' => strlen($rawInput ?? ''),
            'request_body_preview' => substr($rawInput ?? '', 0, 200),
            'recent_logs' => $recentLogs
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

        case 'reports':
            return handleReportsRequest($resourceId, $method, $token, $auth);

        case 'test':
            return handleTestRequest($resourceId, $method, $token, $auth);

        case 'communications':
            return handleCommunicationsRequest($resourceId, $method, $body, $token, $auth);

        case 'media':
            return handleMediaRequest($resourceId, $method, $body, $token, $auth);

        case 'animators':
            return handleAnimatorsRequest($resourceId, $method, $body, $token, $auth);

        case 'wiki':
            return handleWikiRequest($resourceId, $method, $body, $token, $auth);

        case 'public':
            return handlePublicRequest($resourceId, $method, $body, $token, $auth);



        default:
            throw new Exception('Endpoint not found');
    }
}

function handleReportsRequest(?string $reportType, string $method, ?string $token, Auth $auth): array {
    if (!$token) throw new Exception('Authentication required');

    $user = $auth->verifyToken($token);
    if (!$auth->checkPermission($user['id'], 'reports.view')) {
        throw new Exception('Insufficient permissions');
    }

    switch ($reportType) {
        case null:
            // Get available reports
            if ($method !== 'GET') throw new Exception('Method not allowed');

            return [
                'reports' => [
                    [
                        'id' => 'attendance',
                        'name' => 'Attendance Report',
                        'description' => 'Report on child attendance for events',
                        'endpoint' => '/api/reports/attendance'
                    ],
                    [
                        'id' => 'children',
                        'name' => 'Children Report',
                        'description' => 'Report on registered children',
                        'endpoint' => '/api/reports/children'
                    ],
                    [
                        'id' => 'animators',
                        'name' => 'Animators Report',
                        'description' => 'Report on registered animators',
                        'endpoint' => '/api/reports/animators'
                    ]
                ]
            ];

        case 'attendance':
            if ($method !== 'GET') throw new Exception('Method not allowed');

            // Get attendance statistics
            $db = Database::getInstance();

            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-t');

            $stats = $db->fetchAll("
                SELECT
                    e.title as event_title,
                    e.start_date,
                    e.end_date,
                    COUNT(CASE WHEN ar.check_in_time IS NOT NULL THEN 1 END) as checked_in,
                    COUNT(CASE WHEN ar.check_out_time IS NOT NULL THEN 1 END) as checked_out,
                    COUNT(ar.id) as total_registered
                FROM calendar_events e
                LEFT JOIN attendance_records ar ON e.id = ar.event_id
                WHERE e.start_date BETWEEN ? AND ?
                GROUP BY e.id, e.title, e.start_date, e.end_date
                ORDER BY e.start_date DESC
            ", [$startDate, $endDate]);

            return [
                'report_type' => 'attendance',
                'period' => ['start' => $startDate, 'end' => $endDate],
                'data' => $stats
            ];

        case 'children':
            if ($method !== 'GET') throw new Exception('Method not allowed');

            $db = Database::getInstance();

            $stats = $db->fetchAll("
                SELECT
                    COUNT(*) as total_children,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_children,
                    AVG(DATE('now') - DATE(birth_date)) as avg_age_years,
                    COUNT(CASE WHEN DATE('now', '-3 years') > DATE(birth_date) THEN 1 END) as under_3,
                    COUNT(CASE WHEN DATE('now', '-6 years') > DATE(birth_date) AND DATE('now', '-3 years') <= DATE(birth_date) THEN 1 END) as age_3_6,
                    COUNT(CASE WHEN DATE('now', '-12 years') > DATE(birth_date) AND DATE('now', '-6 years') <= DATE(birth_date) THEN 1 END) as age_6_12
                FROM children
            ");

            return [
                'report_type' => 'children',
                'data' => $stats[0] ?? []
            ];

        case 'animators':
            if ($method !== 'GET') throw new Exception('Method not allowed');

            $db = Database::getInstance();

            $stats = $db->fetchAll("
                SELECT
                    COUNT(*) as total_animators,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_animators,
                    COUNT(CASE WHEN hire_date IS NOT NULL THEN 1 END) as hired_animators,
                    AVG(CASE WHEN hire_date IS NOT NULL 
                        THEN (julianday('now') - julianday(hire_date)) / 365.25 
                        END) as avg_years_service
                FROM animators
            ");

            return [
                'report_type' => 'animators',
                'data' => $stats[0] ?? []
            ];

        default:
            throw new Exception('Report type not found');
    }
}

function handlePublicRequest(?string $resourceId, string $method, array $body, ?string $token, Auth $auth): array {
    // Public endpoints that don't require authentication
    switch ($resourceId) {
        case 'communications':
            // Public communications endpoint
            if ($method !== 'GET') throw new Exception('Method not allowed');

            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            return $auth->getCommunications($page, $limit, ['is_public' => 1, 'status' => 'published']);

        case 'calendar':
            // Public calendar events
            if ($method !== 'GET') throw new Exception('Method not allowed');

            $limit = (int)($_GET['limit'] ?? 6);
            $filters = ['is_public' => 1, 'status' => 'published'];
            return $auth->getCalendarEvents(1, $limit, $filters);

        default:
            throw new Exception('Public endpoint not found');
    }
}

function handleWikiRequest(?string $resourceId, string $method, array $body, ?string $token, Auth $auth): array {
    global $pathSegments;

    if (!$token) throw new Exception('Authentication required');

    $user = $auth->verifyToken($token);

    // Check if this is a search operation: /api/wiki/search
    if ($resourceId === 'search') {
        if ($method !== 'GET') throw new Exception('Method not allowed');
        if (!$auth->checkPermission($user['id'], 'wiki.view')) {
            throw new Exception('Insufficient permissions');
        }

        $query = $_GET['q'] ?? '';
        if (empty($query)) {
            throw new Exception('Search query is required');
        }

        $limit = (int)($_GET['limit'] ?? 20);
        return ['results' => $auth->searchWikiPages($query, $limit)];
    }

    // Check if this is a categories operation: /api/wiki/categories
    if ($resourceId === 'categories') {
        switch ($method) {
            case 'GET':
                if (!$auth->checkPermission($user['id'], 'wiki.view')) {
                    throw new Exception('Insufficient permissions');
                }

                return ['categories' => $auth->getWikiCategories()];

            case 'POST':
                if (!$auth->checkPermission($user['id'], 'wiki.moderate')) {
                    throw new Exception('Insufficient permissions');
                }

                $categoryId = $auth->createWikiCategory($body, $user['id']);
                return [
                    'category_id' => $categoryId,
                    'message' => 'Category created successfully'
                ];

            default:
                throw new Exception('Method not allowed');
        }
    }

    // Check if this is a category operation: /api/wiki/categories/{id}
    if ($resourceId === 'categories' && isset($pathSegments[2])) {
        $categoryId = (int)$pathSegments[2];

        switch ($method) {
            case 'GET':
                if (!$auth->checkPermission($user['id'], 'wiki.view')) {
                    throw new Exception('Insufficient permissions');
                }

                $category = $auth->getWikiCategory($categoryId);
                if (!$category) {
                    throw new Exception('Category not found');
                }
                return ['category' => $category];

            case 'PUT':
                if (!$auth->checkPermission($user['id'], 'wiki.moderate')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->updateWikiCategory($categoryId, $body);
                return ['message' => 'Category updated successfully'];

            case 'DELETE':
                if (!$auth->checkPermission($user['id'], 'wiki.moderate')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->deleteWikiCategory($categoryId);
                return ['message' => 'Category deleted successfully'];

            default:
                throw new Exception('Method not allowed');
        }
    }

    // Check if this is a tags operation: /api/wiki/tags
    if ($resourceId === 'tags') {
        switch ($method) {
            case 'GET':
                if (!$auth->checkPermission($user['id'], 'wiki.view')) {
                    throw new Exception('Insufficient permissions');
                }

                return ['tags' => $auth->getWikiTags()];

            case 'POST':
                if (!$auth->checkPermission($user['id'], 'wiki.moderate')) {
                    throw new Exception('Insufficient permissions');
                }

                $tagId = $auth->createWikiTag($body);
                return [
                    'tag_id' => $tagId,
                    'message' => 'Tag created successfully'
                ];

            default:
                throw new Exception('Method not allowed');
        }
    }

    // Check if this is a revisions operation: /api/wiki/{id}/revisions
    if ($resourceId && isset($pathSegments[2]) && $pathSegments[2] === 'revisions') {
        $pageId = (int)$resourceId;

        if ($method !== 'GET') throw new Exception('Method not allowed');
        if (!$auth->checkPermission($user['id'], 'wiki.view')) {
            throw new Exception('Insufficient permissions');
        }

        return ['revisions' => $auth->getWikiPageRevisions($pageId)];
    }

    // Check if this is an attachments operation: /api/wiki/{id}/attachments
    if ($resourceId && isset($pathSegments[2]) && $pathSegments[2] === 'attachments') {
        $pageId = (int)$resourceId;

        switch ($method) {
            case 'GET':
                if (!$auth->checkPermission($user['id'], 'wiki.view')) {
                    throw new Exception('Insufficient permissions');
                }

                return ['attachments' => $auth->getWikiPageAttachments($pageId)];

            case 'POST':
                if (!$auth->checkPermission($user['id'], 'wiki.edit')) {
                    throw new Exception('Insufficient permissions');
                }

                // Handle file upload
                if (isset($_FILES['file'])) {
                    $file = $_FILES['file'];

                    // Validate file
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception('File upload failed: ' . $file['error']);
                    }

                    // Check file size (10MB limit)
                    if ($file['size'] > 10 * 1024 * 1024) {
                        throw new Exception('File size exceeds 10MB limit');
                    }

                    // Create uploads/wiki directory if it doesn't exist
                    $uploadDir = __DIR__ . '/../uploads/wiki/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('wiki_attachment_', true) . '.' . $extension;
                    $filePath = $uploadDir . $filename;

                    // Move uploaded file
                    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                        throw new Exception('Failed to save uploaded file');
                    }

                    // Save attachment info to database
                    $attachmentData = [
                        'original_name' => $file['name'],
                        'file_name' => $filename,
                        'file_path' => $filePath,
                        'file_size' => $file['size'],
                        'mime_type' => $file['type']
                    ];

                    $attachmentId = $auth->addWikiPageAttachment($pageId, $attachmentData, $user['id']);
                    return [
                        'attachment_id' => $attachmentId,
                        'message' => 'Attachment uploaded successfully'
                    ];
                }
                throw new Exception('No file uploaded');

            default:
                throw new Exception('Method not allowed');
        }
    }

    // Check if this is an attachment download: /api/wiki/{id}/attachments/{attachmentId}/download
    if ($resourceId && isset($pathSegments[2]) && $pathSegments[2] === 'attachments' && isset($pathSegments[3]) && isset($pathSegments[4]) && $pathSegments[4] === 'download') {
        $pageId = (int)$resourceId;
        $attachmentId = (int)$pathSegments[3];

        if ($method !== 'GET') throw new Exception('Method not allowed');
        if (!$auth->checkPermission($user['id'], 'wiki.view')) {
            throw new Exception('Insufficient permissions');
        }

        $attachment = $auth->getWikiPageAttachment($attachmentId);
        if (!$attachment || $attachment['page_id'] != $pageId) {
            throw new Exception('Attachment not found');
        }

        // Check if file exists
        if (!file_exists($attachment['file_path'])) {
            throw new Exception('File not found on disk');
        }

        // Clear any previous output
        if (ob_get_level()) {
            ob_clean();
        }

        // Remove the default JSON content-type header
        header_remove('Content-Type');
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Length: ' . filesize($attachment['file_path']));
        header('Content-Disposition: inline; filename="' . $attachment['original_name'] . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($attachment['file_path']);
        exit;
    }

    // Main wiki page operations
    if ($resourceId === null) {
        // Handle collection requests
        switch ($method) {
            case 'GET':
                if (!$auth->checkPermission($user['id'], 'wiki.view')) {
                    throw new Exception('Insufficient permissions');
                }

                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 20);

                $filters = [];
                if (isset($_GET['category_id'])) $filters['category_id'] = (int)$_GET['category_id'];
                if (isset($_GET['tag_id'])) $filters['tag_id'] = (int)$_GET['tag_id'];
                if (isset($_GET['search'])) $filters['search'] = $_GET['search'];
                if (isset($_GET['featured'])) $filters['featured'] = (int)$_GET['featured'];

                return $auth->getWikiPages($page, $limit, $filters);

            case 'POST':
                if (!$auth->checkPermission($user['id'], 'wiki.create')) {
                    throw new Exception('Insufficient permissions');
                }

                $pageId = $auth->createWikiPage($body, $user['id']);
                return [
                    'page_id' => $pageId,
                    'message' => 'Wiki page created successfully'
                ];

            default:
                throw new Exception('Method not allowed');
        }
    } else {
        // Handle individual page requests
        $pageId = (int)$resourceId;

        switch ($method) {
            case 'GET':
                if (!$auth->checkPermission($user['id'], 'wiki.view')) {
                    throw new Exception('Insufficient permissions');
                }

                $page = $auth->getWikiPage($pageId);
                if (!$page) {
                    throw new Exception('Wiki page not found');
                }

                // Increment view count
                $auth->incrementWikiPageViewCount($pageId);

                return ['page' => $page];

            case 'PUT':
                if (!$auth->checkPermission($user['id'], 'wiki.edit')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->updateWikiPage($pageId, $body, $user['id']);
                return ['message' => 'Wiki page updated successfully'];

            case 'DELETE':
                if (!$auth->checkPermission($user['id'], 'wiki.moderate')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->deleteWikiPage($pageId);
                return ['message' => 'Wiki page deleted successfully'];

            default:
                throw new Exception('Method not allowed');
        }
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
    if (!$auth->isAdmin($user['id'])) {
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
                $user = $auth->getUserById($userId);
                if (!$user) {
                    throw new Exception('User not found');
                }
                return ['user' => $user];

            case 'PUT':
                $auth->updateUser($userId, $body);
                return ['message' => 'User updated successfully'];

            case 'DELETE':
                $auth->deleteUser($userId);
                return ['message' => 'User deleted successfully'];

            default:
                throw new Exception('Method not allowed');
        }
    }
}

function handleAnimatorsRequest(?string $animatorId, string $method, array $body, ?string $token, Auth $auth): array {
    global $pathSegments;

    error_log("handleAnimatorsRequest called with animatorId: {$animatorId}, method: {$method}");
    error_log("pathSegments: " . json_encode($pathSegments));

    if (!$token) throw new Exception('Authentication required');

    $user = $auth->verifyToken($token);
    if (!$auth->checkPermission($user['id'], 'registrations.view')) {
        throw new Exception('Insufficient permissions');
    }

    // Check if this is a user linking operation: /api/animators/{id}/users
    if ($animatorId && isset($pathSegments[2]) && $pathSegments[2] === 'users') {
        $animatorId = (int)$animatorId;

        switch ($method) {
            case 'GET':
                // Get users linked to animator
                $users = $auth->getUsersByAnimator($animatorId);
                return ['users' => $users];

            case 'POST':
                if (!$auth->checkPermission($user['id'], 'admin.users')) {
                    throw new Exception('Insufficient permissions');
                }

                $userId = (int)($body['user_id'] ?? 0);
                $relationshipType = $body['relationship_type'] ?? 'primary';
                $notes = $body['notes'] ?? '';

                if (!$userId) {
                    throw new Exception('User ID is required');
                }

                $linkId = $auth->linkAnimatorToUser($animatorId, $userId, $relationshipType, $user['id'], $notes);
                return [
                    'link_id' => $linkId,
                    'message' => 'Animator linked to user successfully'
                ];

            case 'DELETE':
                if (!$auth->checkPermission($user['id'], 'admin.users')) {
                    throw new Exception('Insufficient permissions');
                }

                $userId = (int)($body['user_id'] ?? 0);
                if (!$userId) {
                    throw new Exception('User ID is required');
                }

                $auth->unlinkAnimatorFromUser($animatorId, $userId);
                return ['message' => 'Animator unlinked from user successfully'];

            default:
                throw new Exception('Method not allowed');
        }
    }

    // Check if this is a documents operation: /api/animators/{id}/documents
    if ($animatorId && isset($pathSegments[2]) && $pathSegments[2] === 'documents') {
        $animatorId = (int)$animatorId;
        $documentId = $pathSegments[3] ?? null;

        if ($documentId === null) {
            // Handle collection requests for animator documents
            switch ($method) {
                case 'GET':
                    // Documents are already included in getAnimatorDetails, but we can provide a separate endpoint
                    $animator = $auth->getAnimatorDetails($animatorId);
                    return ['documents' => $animator['documents'] ?? []];

                case 'POST':
                    if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                        throw new Exception('Insufficient permissions');
                    }

                    // Handle file upload
                    if (isset($_FILES['file'])) {
                        $file = $_FILES['file'];

                        // Validate file
                        if ($file['error'] !== UPLOAD_ERR_OK) {
                            throw new Exception('File upload failed: ' . $file['error']);
                        }

                        // Check file size (10MB limit)
                        if ($file['size'] > 10 * 1024 * 1024) {
                            throw new Exception('File size exceeds 10MB limit');
                        }

                        // Create uploads/animators directory if it doesn't exist
                        $uploadDir = __DIR__ . '/../uploads/animators/';
                        if (!is_dir($uploadDir)) {
                            if (!mkdir($uploadDir, 0755, true)) {
                                throw new Exception('Failed to create upload directory');
                            }
                        }

                        // Generate unique filename
                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = uniqid('animator_doc_', true) . '.' . $extension;
                        $filePath = $uploadDir . $filename;

                        // Move uploaded file
                        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                            throw new Exception('Failed to save uploaded file');
                        }

                        // Verify file was saved
                        if (!file_exists($filePath)) {
                            throw new Exception('File was not saved correctly');
                        }

                        // Get real path (but limit length to prevent database issues)
                        $realPath = realpath($filePath);
                        if ($realPath === false) {
                            $realPath = $filePath; // Fallback to relative path
                        }

                        // Truncate path if too long for database
                        if (strlen($realPath) > 500) {
                            $realPath = substr($realPath, -500); // Keep last 500 chars
                        }

                        // Save document info to database
                        $documentData = [
                            'document_type' => $_POST['document_type'] ?? 'other',
                            'original_name' => $file['name'],
                            'file_name' => $filename,
                            'file_path' => $realPath,
                            'file_size' => $file['size'],
                            'mime_type' => $file['type'],
                            'expiry_date' => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null,
                            'notes' => $_POST['notes'] ?? ''
                        ];

                        $documentId = $auth->addAnimatorDocument($animatorId, $documentData, $user['id']);
                        return [
                            'document_id' => $documentId,
                            'message' => 'Document uploaded successfully'
                        ];
                    }
                    throw new Exception('No file uploaded');

                default:
                    throw new Exception('Method not allowed');
            }
        } else {
            // Handle individual document requests
            $documentId = (int)$documentId;

            switch ($method) {
                case 'GET':
                    // For downloading documents
                    // Check for token in query parameter (for direct browser downloads)
                    $queryToken = $_GET['token'] ?? null;
                    if ($queryToken) {
                        try {
                            $user = $auth->verifyToken($queryToken);
                        } catch (Exception $e) {
                            http_response_code(401);
                            echo json_encode(['success' => false, 'error' => 'Invalid token']);
                            exit;
                        }
                    } elseif (!$user) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'Authentication required']);
                        exit;
                    }

                    if (!$auth->checkPermission($user['id'], 'registrations.view')) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
                        exit;
                    }

                    try {
                        $document = $auth->getDb()->fetchOne("SELECT * FROM animator_documents WHERE id = ? AND animator_id = ?", [$documentId, $animatorId]);
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => 'Database error']);
                        exit;
                    }

                    if (!$document) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'error' => 'Document not found']);
                        exit;
                    }

                    // Check if file exists
                    if (!file_exists($document['file_path'])) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'error' => 'File not found on disk']);
                        exit;
                    }

                    // Check if file is readable
                    if (!is_readable($document['file_path'])) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => 'File not readable']);
                        exit;
                    }

                    // Get file size
                    $fileSize = filesize($document['file_path']);
                    if ($fileSize === false) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => 'Could not get file size']);
                        exit;
                    }

                    // Clear any previous output
                    if (ob_get_level()) {
                        ob_clean();
                    }

                    // Remove the default JSON content-type header
                    header_remove('Content-Type');
                    header('Content-Type: ' . $document['mime_type']);
                    header('Content-Length: ' . $fileSize);
                    header('Content-Disposition: inline; filename="' . $document['original_name'] . '"');
                    header('Cache-Control: private, max-age=0, must-revalidate');
                    header('Pragma: public');

                    // Read and output file
                    $fp = fopen($document['file_path'], 'rb');
                    if ($fp === false) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => 'Could not open file']);
                        exit;
                    }

                    while (!feof($fp)) {
                        echo fread($fp, 8192);
                    }
                    fclose($fp);
                    exit;

                case 'DELETE':
                    if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                        throw new Exception('Insufficient permissions');
                    }

                    // Get document info
                    $document = $auth->getDb()->fetchOne("SELECT * FROM animator_documents WHERE id = ? AND animator_id = ?", [$documentId, $animatorId]);
                    if ($document && file_exists($document['file_path'])) {
                        unlink($document['file_path']);
                    }

                    $auth->getDb()->delete('animator_documents', 'id = ? AND animator_id = ?', [$documentId, $animatorId]);
                    return ['message' => 'Document deleted successfully'];

                default:
                    throw new Exception('Method not allowed');
            }
        }
    }

    // Check if this is a notes operation: /api/animators/{id}/notes
    if ($animatorId && isset($pathSegments[2]) && $pathSegments[2] === 'notes') {
        $animatorId = (int)$animatorId;
        $noteId = $pathSegments[3] ?? null;

        if ($noteId) {
            $noteId = (int)$noteId;
            switch ($method) {
                case 'DELETE':
                    if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                        throw new Exception('Insufficient permissions');
                    }
                    $auth->getDb()->delete('animator_notes', 'id = ? AND animator_id = ?', [$noteId, $animatorId]);
                    return ['message' => 'Note deleted successfully'];
                default:
                    throw new Exception('Method not allowed for individual note');
            }
        }

        switch ($method) {
            case 'GET':
                if (!$auth->checkPermission($user['id'], 'registrations.view')) {
                    throw new Exception('Insufficient permissions');
                }

                // Get notes for animator
                $notes = $auth->getDb()->fetchAll("
                    SELECT an.*, u.username as created_by_name
                    FROM animator_notes an
                    JOIN users u ON an.created_by = u.id
                    WHERE an.animator_id = ?
                    ORDER BY an.created_at DESC
                ", [$animatorId]);

                return ['notes' => $notes];

            case 'POST':
                if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                    throw new Exception('Insufficient permissions');
                }

                $noteType = $body['note_type'] ?? 'observation';
                $title = $body['title'] ?? '';
                $content = $body['content'] ?? '';
                $isPrivate = (bool)($body['is_private'] ?? false);

                if (empty($title) || empty($content)) {
                    throw new Exception('Title and content are required');
                }

                $noteData = [
                    'note_type' => $noteType,
                    'title' => $title,
                    'content' => $content,
                    'is_private' => $isPrivate
                ];

                $noteId = $auth->addAnimatorNote($animatorId, $noteData, $user['id']);
                return [
                    'note_id' => $noteId,
                    'message' => 'Note added successfully'
                ];

            default:
                throw new Exception('Method not allowed');
        }
    }

    // Check if this is a week type availability operation: /api/animators/{id}/week-types/{weekTypeId}/availability
    if ($animatorId && isset($pathSegments[2]) && $pathSegments[2] === 'week-types' && isset($pathSegments[3]) && isset($pathSegments[4]) && $pathSegments[4] === 'availability') {
        $animatorId = (int)$animatorId;
        $weekTypeId = (int)$pathSegments[3];

        switch ($method) {
            case 'GET':
                if (!$auth->checkPermission($user['id'], 'registrations.view')) {
                    throw new Exception('Insufficient permissions');
                }

                $availability = $auth->getAnimatorWeekAvailability($weekTypeId);
                return ['availability' => $availability];

            case 'POST':
                if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                    throw new Exception('Insufficient permissions');
                }

                // Log incoming data for debugging
                error_log("API POST Availability - Animator: {$animatorId}, WeekType: {$weekTypeId}");
                error_log("API POST Availability - Request Body: " . json_encode($body));
                error_log("API POST Availability - Request Body type: " . gettype($body));
                error_log("API POST Availability - Request Body is_array: " . (is_array($body) ? 'yes' : 'no'));

                // Verify that the week type belongs to the animator
                try {
                    $weekType = $auth->getDb()->fetchOne("SELECT id FROM animator_week_types WHERE id = ? AND animator_id = ?", [$weekTypeId, $animatorId]);
                    error_log("API POST Availability - Week type query result: " . json_encode($weekType));
                } catch (Exception $e) {
                    error_log("API POST Availability - Database error checking week type: " . $e->getMessage());
                    throw $e;
                }

                if (!$weekType) {
                    error_log("API POST Availability - Week type not found or doesn't belong to animator");
                    throw new Exception('Week type not found or does not belong to this animator');
                }

                $availabilityData = $body;
                if (empty($availabilityData)) {
                    error_log("API POST Availability - Empty availability data");
                    throw new Exception('Availability data is required');
                }

                error_log("API POST Availability - Calling setAnimatorWeekAvailability with " . count($availabilityData) . " items");

                try {
                    $count = $auth->setAnimatorWeekAvailability($weekTypeId, $availabilityData);
                    error_log("API POST Availability - Successfully updated {$count} availability records");
                } catch (Exception $e) {
                    error_log("API POST Availability - Error in setAnimatorWeekAvailability: " . $e->getMessage());
                    error_log("API POST Availability - Error stack trace: " . $e->getTraceAsString());
                    throw $e;
                }

                return [
                    'count' => $count,
                    'message' => 'Week type availability updated successfully'
                ];

            default:
                throw new Exception('Method not allowed');
        }
    }

    // Check if this is an availability operation: /api/animators/{id}/availability
    if ($animatorId && isset($pathSegments[2]) && $pathSegments[2] === 'availability') {
        $animatorId = (int)$animatorId;

        switch ($method) {
            case 'GET':
                if (!$auth->checkPermission($user['id'], 'registrations.view')) {
                    throw new Exception('Insufficient permissions');
                }

                $availability = $auth->getAnimatorAvailability($animatorId);

                // Debug logging
                error_log("API GET Availability for animator {$animatorId}: " . json_encode($availability));

                return $availability;

            case 'POST':
            case 'PUT':
                if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                    throw new Exception('Insufficient permissions');
                }

                // Get the animator's default week type (first one created)
                $weekTypes = $auth->getAnimatorWeekTypes($animatorId);
                if (empty($weekTypes)) {
                    throw new Exception('No week types found for animator');
                }

                $defaultWeekType = $weekTypes[0]; // Use first week type as default

                // Update the availability for this week type
                $count = $auth->setAnimatorWeekAvailability($defaultWeekType['id'], $body);

                return [
                    'count' => $count,
                    'message' => 'Availability updated successfully'
                ];

            default:
                throw new Exception('Method not allowed');
        }
    }

    // Check if this is a week types operation: /api/animators/{id}/week-types
    if ($animatorId && isset($pathSegments[2]) && $pathSegments[2] === 'week-types' && !isset($pathSegments[4])) {
        $animatorId = (int)$animatorId;
        $weekTypeId = isset($pathSegments[3]) ? (int)$pathSegments[3] : null;

        if ($weekTypeId === null) {
            // Handle collection requests for week types
            switch ($method) {
                case 'GET':
                    if (!$auth->checkPermission($user['id'], 'registrations.view')) {
                        throw new Exception('Insufficient permissions');
                    }

                    $weekTypes = $auth->getAnimatorWeekTypes($animatorId);
                    return ['week_types' => $weekTypes];

                case 'POST':
                    if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                        throw new Exception('Insufficient permissions');
                    }

                    $weekTypeId = $auth->createAnimatorWeekType($animatorId, $body, $user['id']);
                    return [
                        'week_type_id' => $weekTypeId,
                        'message' => 'Week type created successfully'
                    ];

                default:
                    throw new Exception('Method not allowed');
            }
        } else {
            // Handle individual week type requests
            switch ($method) {
                case 'GET':
                    if (!$auth->checkPermission($user['id'], 'registrations.view')) {
                        throw new Exception('Insufficient permissions');
                    }

                    $weekType = $auth->getAnimatorWeekTypes($animatorId);
                    $weekType = array_filter($weekType, fn($wt) => $wt['id'] == $weekTypeId);
                    if (empty($weekType)) {
                        throw new Exception('Week type not found');
                    }

                    $weekType = array_values($weekType)[0];
                    $weekType['availability'] = $auth->getAnimatorWeekAvailability($weekTypeId);
                    return ['week_type' => $weekType];

                case 'PUT':
                    if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                        throw new Exception('Insufficient permissions');
                    }

                    $auth->updateAnimatorWeekType($weekTypeId, $body);
                    return ['message' => 'Week type updated successfully'];

                case 'DELETE':
                    if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                        throw new Exception('Insufficient permissions');
                    }

                    $auth->deleteAnimatorWeekType($weekTypeId);
                    return ['message' => 'Week type deleted successfully'];

                default:
                    throw new Exception('Method not allowed');
            }
        }
    }

    // Check if this is a template operation: /api/animators/templates
    if ($animatorId === 'templates') {
        $templateId = isset($pathSegments[2]) ? (int)$pathSegments[2] : null;

        switch ($method) {
            case 'GET':
                if (!$auth->checkPermission($user['id'], 'registrations.view')) {
                    throw new Exception('Insufficient permissions');
                }

                if ($templateId) {
                    // Get specific template
                    $templates = $auth->getAvailabilityTemplates();
                    $template = array_filter($templates, fn($t) => $t['id'] == $templateId);
                    if (empty($template)) {
                        throw new Exception('Template not found');
                    }
                    return ['template' => array_values($template)[0]];
                } else {
                    // Get all templates
                    return ['templates' => $auth->getAvailabilityTemplates()];
                }

            case 'POST':
                if (!$auth->checkPermission($user['id'], 'admin.system')) {
                    throw new Exception('Insufficient permissions');
                }

                $templateId = $auth->createAvailabilityTemplate($body, $user['id']);
                return [
                    'template_id' => $templateId,
                    'message' => 'Availability template created successfully'
                ];

            case 'PUT':
                if (!$auth->checkPermission($user['id'], 'admin.system')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->updateAvailabilityTemplate($templateId, $body);
                return ['message' => 'Availability template updated successfully'];

            case 'DELETE':
                if (!$auth->checkPermission($user['id'], 'admin.system')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->deleteAvailabilityTemplate($templateId);
                return ['message' => 'Availability template deleted successfully'];

            default:
                throw new Exception('Method not allowed');
        }
    }



    // Check if this is a template availability operation: /api/animators/templates/{id}/availability
    if ($animatorId === 'templates' && isset($pathSegments[2]) && isset($pathSegments[3]) && $pathSegments[3] === 'availability') {
        $templateId = (int)$pathSegments[2];

        switch ($method) {
            case 'GET':
                if (!$auth->checkPermission($user['id'], 'registrations.view')) {
                    throw new Exception('Insufficient permissions');
                }

                $availability = $auth->getTemplateAvailability($templateId);
                return ['availability' => $availability];

            case 'POST':
                if (!$auth->checkPermission($user['id'], 'admin.system')) {
                    throw new Exception('Insufficient permissions');
                }

                $availabilityData = $body;
                if (empty($availabilityData)) {
                    throw new Exception('Availability data is required');
                }

                $count = $auth->setTemplateAvailability($templateId, $availabilityData);
                return [
                    'count' => $count,
                    'message' => 'Template availability updated successfully'
                ];

            default:
                throw new Exception('Method not allowed');
        }
    }

    if ($animatorId === null) {
        // Handle collection requests
        switch ($method) {
            case 'GET':
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 20);

                $filters = [];
                if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
                if (isset($_GET['search'])) $filters['search'] = $_GET['search'];

                return $auth->getAnimators($page, $limit, $filters);

            case 'POST':
                if (!$auth->checkPermission($user['id'], 'registrations.create')) {
                    throw new Exception('Insufficient permissions');
                }

                $animatorId = $auth->createAnimator($body, $user['id']);
                return [
                    'animator_id' => $animatorId,
                    'message' => 'Animator created successfully'
                ];

            default:
                throw new Exception('Method not allowed');
        }
    } else {
        // Handle individual animator requests
        $animatorId = (int)$animatorId;

        switch ($method) {
            case 'GET':
                return ['animator' => $auth->getAnimatorDetails($animatorId)];

            case 'PUT':
                if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->updateAnimator($animatorId, $body, $user['id']);
                return ['message' => 'Animator information updated successfully'];

            case 'DELETE':
                if (!$auth->checkPermission($user['id'], 'registrations.delete')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->deleteAnimator($animatorId);
                return ['message' => 'Animator deleted successfully'];

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
    global $pathSegments;

    if (!$token) throw new Exception('Authentication required');

    $user = $auth->verifyToken($token);
    if (!$auth->checkPermission($user['id'], 'registrations.view')) {
        throw new Exception('Insufficient permissions');
    }

    // Check if this is a sub-resource request: /api/children/{id}/{resource}
    if ($childId && isset($pathSegments[2])) {
        $childId = (int)$childId;
        $resource = $pathSegments[2];
        $resourceId = $pathSegments[3] ?? null;

        switch ($resource) {
            case 'guardians':
                return handleChildGuardiansRequest($childId, $resourceId, $method, $body, $user, $auth);

            case 'documents':
                return handleChildDocumentsRequest($childId, $resourceId, $method, $body, $user, $auth);

            case 'notes':
                return handleChildNotesRequest($childId, $resourceId, $method, $body, $user, $auth);

            default:
                throw new Exception('Unknown child resource');
        }
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

function handleChildGuardiansRequest(int $childId, ?string $guardianId, string $method, array $body, array $user, Auth $auth): array {
    if ($guardianId === null) {
        // Handle collection requests for child guardians
        switch ($method) {
            case 'GET':
                // Guardians are already included in getChildDetails, but we can provide a separate endpoint
                $child = $auth->getChildDetails($childId);
                return ['guardians' => $child['guardians'] ?? []];

            case 'POST':
                if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                    throw new Exception('Insufficient permissions');
                }

                $guardianId = $auth->addChildGuardian($childId, $body);
                return [
                    'guardian_id' => $guardianId,
                    'message' => 'Guardian added successfully'
                ];

            default:
                throw new Exception('Method not allowed');
        }
    } else {
        // Handle individual guardian requests
        $guardianId = (int)$guardianId;

        switch ($method) {
            case 'PUT':
                if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->updateChildGuardian($guardianId, $body);
                return ['message' => 'Guardian updated successfully'];

            case 'DELETE':
                if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                    throw new Exception('Insufficient permissions');
                }

                // For deletion, we need to remove the guardian from the child_guardians table
                $auth->getDb()->delete('child_guardians', 'id = ? AND child_id = ?', [$guardianId, $childId]);
                return ['message' => 'Guardian removed successfully'];

            default:
                throw new Exception('Method not allowed');
        }
    }
}

function handleChildDocumentsRequest(int $childId, ?string $documentId, string $method, array $body, array $user, Auth $auth): array {
    if ($documentId === null) {
        // Handle collection requests for child documents
        switch ($method) {
            case 'GET':
                // Documents are already included in getChildDetails, but we can provide a separate endpoint
                $child = $auth->getChildDetails($childId);
                return ['documents' => $child['documents'] ?? []];

            case 'POST':
                if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                    throw new Exception('Insufficient permissions');
                }

                // Handle file upload
                if (isset($_FILES['file'])) {
                    $file = $_FILES['file'];

                    // Validate file
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception('File upload failed: ' . $file['error']);
                    }

                    // Check file size (10MB limit)
                    if ($file['size'] > 10 * 1024 * 1024) {
                        throw new Exception('File size exceeds 10MB limit');
                    }

                    // Create uploads/children directory if it doesn't exist
                    $uploadDir = __DIR__ . '/../uploads/children/';
                    if (!is_dir($uploadDir)) {
                        if (!mkdir($uploadDir, 0755, true)) {
                            throw new Exception('Failed to create upload directory');
                        }
                    }

                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('child_doc_', true) . '.' . $extension;
                    $filePath = $uploadDir . $filename;

                    // Move uploaded file
                    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                        throw new Exception('Failed to save uploaded file');
                    }

                    // Verify file was saved
                    if (!file_exists($filePath)) {
                        throw new Exception('File was not saved correctly');
                    }

                    // Get real path (but limit length to prevent database issues)
                    $realPath = realpath($filePath);
                    if ($realPath === false) {
                        $realPath = $filePath; // Fallback to relative path
                    }

                    // Truncate path if too long for database
                    if (strlen($realPath) > 500) {
                        $realPath = substr($realPath, -500); // Keep last 500 chars
                    }

                    // Save document info to database
                    $documentData = [
                        'document_type' => $_POST['document_type'] ?? 'other',
                        'original_name' => $file['name'],
                        'file_name' => $filename,
                        'file_path' => $realPath,
                        'file_size' => $file['size'],
                        'mime_type' => $file['type'],
                        'expiry_date' => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null,
                        'notes' => $_POST['notes'] ?? ''
                    ];

                    $documentId = $auth->addChildDocument($childId, $documentData, $user['id']);
                    return [
                        'document_id' => $documentId,
                        'message' => 'Document uploaded successfully'
                    ];
                }
                throw new Exception('No file uploaded');

            default:
                throw new Exception('Method not allowed');
        }
    } else {
        // Handle individual document requests
        $documentId = (int)$documentId;

        switch ($method) {
            case 'GET':
                // For downloading documents
                // Check for token in query parameter (for direct browser downloads)
                $queryToken = $_GET['token'] ?? null;
                if ($queryToken) {
                    try {
                        $user = $auth->verifyToken($queryToken);
                    } catch (Exception $e) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'Invalid token']);
                        exit;
                    }
                } elseif (!$user) {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'error' => 'Authentication required']);
                    exit;
                }

                if (!$auth->checkPermission($user['id'], 'registrations.view')) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
                    exit;
                }

                try {
                    $document = $auth->getDb()->fetchOne("SELECT * FROM child_documents WHERE id = ? AND child_id = ?", [$documentId, $childId]);
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Database error']);
                    exit;
                }

                if (!$document) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Document not found']);
                    exit;
                }

                // Check if file exists
                if (!file_exists($document['file_path'])) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'File not found on disk']);
                    exit;
                }

                // Check if file is readable
                if (!is_readable($document['file_path'])) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'File not readable']);
                    exit;
                }

                // Get file size
                $fileSize = filesize($document['file_path']);
                if ($fileSize === false) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Could not get file size']);
                    exit;
                }

                // Clear any previous output
                if (ob_get_level()) {
                    ob_clean();
                }

                // Remove the default JSON content-type header
                header_remove('Content-Type');
                header('Content-Type: ' . $document['mime_type']);
                header('Content-Length: ' . $fileSize);
                header('Content-Disposition: inline; filename="' . $document['original_name'] . '"');
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');

                // Read and output file
                $fp = fopen($document['file_path'], 'rb');
                if ($fp === false) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Could not open file']);
                    exit;
                }

                while (!feof($fp)) {
                    echo fread($fp, 8192);
                }
                fclose($fp);
                exit;

            case 'DELETE':
                if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                    throw new Exception('Insufficient permissions');
                }

                // Get document info
                $document = $auth->getDb()->fetchOne("SELECT * FROM child_documents WHERE id = ? AND child_id = ?", [$documentId, $childId]);
                if ($document && file_exists($document['file_path'])) {
                    unlink($document['file_path']);
                }

                $auth->getDb()->delete('child_documents', 'id = ? AND child_id = ?', [$documentId, $childId]);
                return ['message' => 'Document deleted successfully'];

            default:
                throw new Exception('Method not allowed');
        }
    }
}

function handleChildNotesRequest(int $childId, ?string $noteId, string $method, array $body, array $user, Auth $auth): array {
    if ($noteId === null) {
        // Handle collection requests for child notes
        switch ($method) {
            case 'GET':
                // Notes are already included in getChildDetails, but we can provide a separate endpoint
                $child = $auth->getChildDetails($childId);
                return ['notes' => $child['notes'] ?? []];

            case 'POST':
                if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                    throw new Exception('Insufficient permissions');
                }

                $noteData = [
                    'note_type' => $body['note_type'] ?? 'observation',
                    'title' => $body['title'] ?? '',
                    'content' => $body['content'] ?? '',
                    'is_private' => (bool)($body['is_private'] ?? false)
                ];

                if (empty($noteData['title']) || empty($noteData['content'])) {
                    throw new Exception('Title and content are required');
                }

                $noteId = $auth->addChildNote($childId, $noteData, $user['id']);
                return [
                    'note_id' => $noteId,
                    'message' => 'Note added successfully'
                ];

            default:
                throw new Exception('Method not allowed');
        }
    } else {
        // Handle individual note requests
        $noteId = (int)$noteId;

        switch ($method) {
            case 'PUT':
                if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                    throw new Exception('Insufficient permissions');
                }

                $updateData = [];
                if (isset($body['title'])) $updateData['title'] = $body['title'];
                if (isset($body['content'])) $updateData['content'] = $body['content'];
                if (isset($body['is_private'])) $updateData['is_private'] = (bool)$body['is_private'];

                if (!empty($updateData)) {
                    $updateData['updated_at'] = date('Y-m-d H:i:s');
                    $auth->getDb()->update('child_notes', $updateData, 'id = ? AND child_id = ?', [$noteId, $childId]);
                }

                return ['message' => 'Note updated successfully'];

            case 'DELETE':
                if (!$auth->checkPermission($user['id'], 'registrations.edit')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->getDb()->delete('child_notes', 'id = ? AND child_id = ?', [$noteId, $childId]);
                return ['message' => 'Note deleted successfully'];

            default:
                throw new Exception('Method not allowed');
        }
    }
}

function handleTestRequest(?string $action, string $method, ?string $token, Auth $auth): array {
    switch ($method) {
        case 'GET':
            return [
                'message' => 'Test endpoint working',
                'timestamp' => time(),
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
            ];

        default:
            throw new Exception('Method not allowed');
    }
}

function handleSystemRequest(?string $action, string $method, ?string $token, Auth $auth): array {
    if (!$token) throw new Exception('Authentication required');

    $user = $auth->verifyToken($token);
    if (!$auth->checkPermission($user['id'], 'admin.system.view')) {
        throw new Exception('Insufficient permissions');
    }

    switch ($action) {
        case 'config':
            if ($method !== 'GET') throw new Exception('Method not allowed');
            // Only return a subset of the config for security reasons
            return [
                'config' => [
                    'features' => $auth->getConfig()['features']
                ]
            ];
        case 'status':
            if ($method !== 'GET') throw new Exception('Method not allowed');

            try {
                $config = require __DIR__ . '/../config/config.php';

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
            $checkInTime = $body['check_in_time'] ?? null;

            if (!$childId || !$eventId) {
                throw new Exception('Child ID and Event ID are required');
            }

            $auth->checkInOutChild($childId, $eventId, 'checkin', $user['id'], $notes, $checkInTime);
            return ['message' => 'Check-in recorded successfully'];

        case 'checkout':
            if ($method !== 'POST') throw new Exception('Method not allowed');
            if (!$auth->checkPermission($user['id'], 'attendance.checkin')) {
                throw new Exception('Insufficient permissions');
            }

            $childId = (int)($body['child_id'] ?? 0);
            $eventId = (int)($body['event_id'] ?? 0);
            $notes = $body['notes'] ?? '';
            $checkOutTime = $body['check_out_time'] ?? null;

            if (!$childId || !$eventId) {
                throw new Exception('Child ID and Event ID are required');
            }

            $auth->checkInOutChild($childId, $eventId, 'checkout', $user['id'], $notes, $checkOutTime);
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

        case 'register':
            // Get event register (participants + attendance)
            if ($method !== 'GET') throw new Exception('Method not allowed');
            
            if (!$auth->checkPermission($user['id'], 'attendance.view')) {
                throw new Exception('Insufficient permissions');
            }

            $eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
            $date = $_GET['date'] ?? date('Y-m-d');
            
            if (!$eventId) throw new Exception('Event ID is required');

            try {
                $result = $auth->getEventRegister($eventId, $date);
                return ['register' => $result];
            } catch (Exception $e) {
                throw $e;
            }

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

function handleMediaRequest(?string $resourceId, string $method, array $body, ?string $token, Auth $auth): array {
    global $pathSegments;

    $user = null;

    // For shared resources, token is optional
    if ($token) {
        $user = $auth->verifyToken($token);
    }

    // Check if this is a shared resource: /api/media/shared/{token}
    if ($resourceId === 'shared' && isset($pathSegments[2])) {
        $shareToken = $pathSegments[2];

        switch ($method) {
            case 'GET':
                $resource = $auth->getSharedResource($shareToken);
                if (!$resource) {
                    throw new Exception('Shared resource not found or expired');
                }
                return ['resource' => $resource];

            default:
                throw new Exception('Method not allowed');
        }
    }

    // Check if this is a folder operation: /api/media/folders/{id}
    if ($resourceId === 'folders' && isset($pathSegments[2])) {
        $folderId = (int)$pathSegments[2];

        // Check if this is a shared folder access
        $shareToken = $_GET['token'] ?? null;
        $isSharedAccess = false;

        if ($shareToken) {
            $sharing = $auth->getDb()->fetchOne("
                SELECT * FROM media_sharing
                WHERE share_token = ? AND resource_type = 'folder' AND resource_id = ? AND (expires_at IS NULL OR expires_at > datetime('now'))
            ", [$shareToken, $folderId]);

            if ($sharing) {
                $isSharedAccess = true;
            }
        }

        if (!$isSharedAccess && !$user) {
            throw new Exception('Authentication required');
        }

        if (!$isSharedAccess && !$auth->checkPermission($user['id'], 'media.view')) {
            throw new Exception('Insufficient permissions');
        }

        switch ($method) {
            case 'GET':
                // Get folder details and contents
                $folder = $auth->getDb()->fetchOne("SELECT * FROM media_folders WHERE id = ?", [$folderId]);
                if (!$folder) {
                    throw new Exception('Folder not found');
                }

                $contents = $auth->getFolderContents($folderId);
                return array_merge($folder, ['contents' => $contents]);

            case 'PUT':
                if ($isSharedAccess) {
                    throw new Exception('Operation not allowed for shared content');
                }

                if (!$auth->checkPermission($user['id'], 'media.upload')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->moveMediaFolder($folderId, $body['parent_id'] ?? null);
                return ['message' => 'Folder moved successfully'];

            case 'DELETE':
                if ($isSharedAccess) {
                    throw new Exception('Operation not allowed for shared content');
                }

                if (!$auth->checkPermission($user['id'], 'media.delete')) {
                    throw new Exception('Insufficient permissions');
                }

                // Delete folder and all its contents
                $auth->deleteMediaFolder($folderId);
                return ['message' => 'Folder deleted successfully'];

            default:
                throw new Exception('Method not allowed');
        }
    }

    // Check if this is a file operation: /api/media/files/{id}
    if ($resourceId === 'files' && isset($pathSegments[2])) {
        $fileId = (int)$pathSegments[2];

        // Check if this is a download request: /api/media/files/{id}/download
        if (isset($pathSegments[3]) && $pathSegments[3] === 'download') {
            // Check for token in query parameter (for direct browser downloads)
            $queryToken = $_GET['token'] ?? null;
            if ($queryToken) {
                try {
                    $user = $auth->verifyToken($queryToken);
                } catch (Exception $e) {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'error' => 'Invalid token']);
                    exit;
                }
            } elseif (!$user) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Authentication required']);
                exit;
            }

            if (!$auth->checkPermission($user['id'], 'media.view')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
                exit;
            }

            $file = $auth->getMediaFile($fileId);
            if (!$file) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'File not found']);
                exit;
            }

            // Check if file exists on disk
            if (!file_exists($file['file_path'])) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'File not found on disk']);
                exit;
            }

            // For text files requested via fetch (for preview), return content as JSON
            $isTextFile = in_array($file['mime_type'], [
                'text/plain', 'text/csv', 'text/markdown', 'text/html', 'text/css',
                'text/javascript', 'application/json', 'application/xml', 'application/javascript'
            ]) || strpos($file['mime_type'], 'text/') === 0;

            if ($isTextFile && isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                // Return file content as JSON for preview
                $fileSize = filesize($file['file_path']);

                // For very large files, show a preview message instead
                if ($fileSize > 100 * 1024) { // 100KB limit
                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'content' => "File too large to preview ({$fileSize} bytes). Use download instead.",
                        'mime_type' => $file['mime_type'],
                        'file_name' => $file['original_name'],
                        'truncated' => false,
                        'file_size' => $fileSize
                    ]);
                    exit;
                }

                // Read file content with error handling
                $content = @file_get_contents($file['file_path']);
                if ($content === false) {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Failed to read file content',
                        'debug' => [
                            'file_path' => $file['file_path'],
                            'file_exists' => file_exists($file['file_path']),
                            'file_readable' => is_readable($file['file_path']),
                            'file_size' => $fileSize
                        ]
                    ]);
                    exit;
                }

                // Convert encoding if needed
                if (!mb_check_encoding($content, 'UTF-8')) {
                    $content = mb_convert_encoding($content, 'UTF-8', 'auto');
                }

                // Limit preview to 512 bytes for safety
                $maxPreviewSize = 512;
                $truncated = false;
                if (strlen($content) > $maxPreviewSize) {
                    $content = mb_substr($content, 0, $maxPreviewSize, 'UTF-8') . "\n\n[Content truncated for preview]";
                    $truncated = true;
                }

                // Ensure content is valid UTF-8
                $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');

                // Base64 encode content to avoid JSON encoding issues
                $responseData = [
                    'success' => true,
                    'content' => base64_encode($content),
                    'encoding' => 'base64',
                    'mime_type' => $file['mime_type'],
                    'file_name' => $file['original_name'],
                    'truncated' => $truncated,
                    'file_size' => $fileSize
                ];

                $jsonResult = json_encode($responseData);
                if ($jsonResult === false) {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Failed to encode response: ' . json_last_error_msg()
                    ]);
                    exit;
                }

                // Increment download count
                $auth->getDb()->query("UPDATE media_files SET download_count = download_count + 1 WHERE id = ?", [$fileId]);

                http_response_code(200);
                echo $jsonResult;
                exit;
            }

            // For direct downloads or non-text files, serve the file directly
                // Increment download count
                $auth->getDb()->query("UPDATE media_files SET download_count = download_count + 1 WHERE id = ?", [$fileId]);

            // Clear any previous output and headers
            ob_clean();

            // Remove the default JSON content-type header
            header_remove('Content-Type');
            header('Content-Type: ' . $file['mime_type']);
            header('Content-Length: ' . filesize($file['file_path']));
            header('Content-Disposition: inline; filename="' . $file['original_name'] . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');

            readfile($file['file_path']);
            exit;
        }

        if (!$user) throw new Exception('Authentication required');

        switch ($method) {
            case 'GET':
                if (!$auth->checkPermission($user['id'], 'media.view')) {
                    throw new Exception('Insufficient permissions');
                }

                $file = $auth->getMediaFile($fileId);
                if (!$file) {
                    throw new Exception('File not found');
                }
                return ['file' => $file];

            case 'PUT':
                if (!$auth->checkPermission($user['id'], 'media.upload')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->moveMediaFile($fileId, $body['folder_id'] ?? null);
                return ['message' => 'File moved successfully'];

            case 'DELETE':
                if (!$auth->checkPermission($user['id'], 'media.delete')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->deleteMediaFile($fileId);
                return ['message' => 'File deleted successfully'];

            default:
                throw new Exception('Method not allowed');
        }
    }

    // Check if this is a sharing operation: /api/media/share
    if ($resourceId === 'share') {
        if (!$user) throw new Exception('Authentication required');

        switch ($method) {
            case 'POST':
                if (!$auth->checkPermission($user['id'], 'media.upload')) {
                    throw new Exception('Insufficient permissions');
                }

                $resourceType = $body['resource_type'] ?? '';
                $resourceId = (int)($body['resource_id'] ?? 0);
                $permission = $body['permission'] ?? 'view';
                $expiresHours = (int)($body['expires_hours'] ?? 24);

                if (!$resourceType || !$resourceId) {
                    throw new Exception('Resource type and ID are required');
                }

                $shareToken = $auth->createShareLink($resourceType, $resourceId, $user['id'], $permission, $expiresHours);
                return [
                    'share_token' => $shareToken,
                    'share_url' => "/shared.html?token={$shareToken}",
                    'message' => 'Share link created successfully'
                ];

            default:
                throw new Exception('Method not allowed');
        }
    }

    // Main media endpoints
    if ($resourceId === null) {
        // Handle collection requests
        switch ($method) {
            case 'GET':
                if (!$user || !$auth->checkPermission($user['id'], 'media.view')) {
                    throw new Exception('Authentication required or insufficient permissions');
                }

                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 20);
                $folderId = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : null;
                $search = $_GET['search'] ?? '';

                $folders = $auth->getMediaFolders($page, $limit, $folderId)['folders'];
                $files = $auth->getMediaFiles($page, $limit, $folderId, $search)['files'];

                return [
                    'folders' => $folders,
                    'files' => $files,
                    'total_items' => count($folders) + count($files)
                ];

            case 'POST':
                if (!$user || !$auth->checkPermission($user['id'], 'media.upload')) {
                    throw new Exception('Authentication required or insufficient permissions');
                }

                // Handle file upload (FormData)
                if (isset($_FILES['file'])) {
                    $file = $_FILES['file'];

                    // Validate file
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception('File upload failed: ' . $file['error']);
                    }

                    // Check file size (10MB limit)
                    if ($file['size'] > 10 * 1024 * 1024) {
                        throw new Exception('File size exceeds 10MB limit');
                    }

                    // Create uploads/media directory if it doesn't exist
                    $uploadDir = __DIR__ . '/../uploads/media/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('media_', true) . '.' . $extension;
                    $filePath = $uploadDir . $filename;

                    // Move uploaded file
                    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                        throw new Exception('Failed to save uploaded file');
                    }

                    // Save file info to database
                    $fileData = [
                        'original_name' => $file['name'],
                        'file_path' => $filePath,
                        'file_size' => $file['size'],
                        'mime_type' => $file['type']
                    ];

                    // Only set folder_id if it's provided and not empty
                    $folderId = $_POST['folder_id'] ?? null;
                    if (!empty($folderId) && $folderId !== 'null') {
                        $fileData['folder_id'] = (int)$folderId;
                    }

                    $fileId = $auth->uploadMediaFile($fileData, $user['id']);
                    return [
                        'file_id' => $fileId,
                        'message' => 'File uploaded successfully'
                    ];
                }

                // Handle folder creation (JSON)
                if (isset($body['name']) && isset($body['type']) && $body['type'] === 'folder') {
                    $folderData = [
                        'name' => $body['name'],
                        'description' => $body['description'] ?? ''
                    ];

                    // Only set parent_id if it's provided and not null
                    if (isset($body['parent_id']) && $body['parent_id'] !== null) {
                        $folderData['parent_id'] = (int)$body['parent_id'];
                    }

                    $folderId = $auth->createMediaFolder($folderData, $user['id']);
                    return [
                        'folder_id' => $folderId,
                        'message' => 'Folder created successfully'
                    ];
                }

                throw new Exception('Invalid request data');

            default:
                throw new Exception('Method not allowed');
        }
    } else {
        // Handle individual resource requests (legacy support)
        $resourceId = (int)$resourceId;

        if (!$user) throw new Exception('Authentication required');

        switch ($method) {
            case 'GET':
                if (!$auth->checkPermission($user['id'], 'media.view')) {
                    throw new Exception('Insufficient permissions');
                }

                $file = $auth->getMediaFile($resourceId);
                if (!$file) {
                    throw new Exception('File not found');
                }
                return ['file' => $file];

            case 'DELETE':
                if (!$auth->checkPermission($user['id'], 'media.delete')) {
                    throw new Exception('Insufficient permissions');
                }

                $auth->deleteMediaFile($resourceId);
                return ['message' => 'File deleted successfully'];

            default:
                throw new Exception('Method not allowed');
        }
    }
}
