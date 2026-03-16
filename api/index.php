<?php

/**
 * AnimaID API - Slim-based Router
 * Modern routing using Slim Framework with proper controllers and middleware
 */

use Slim\Factory\AppFactory;
use AnimaID\Config\ConfigManager;
use AnimaID\Security\JwtManager;
use AnimaID\Repositories\AnimatorRepository;
use AnimaID\Repositories\AttendanceRepository;
use AnimaID\Repositories\CalendarRepository;
use AnimaID\Repositories\ChildRepository;
use AnimaID\Repositories\CommunicationRepository;
use AnimaID\Repositories\MediaRepository;
use AnimaID\Repositories\PermissionRepository;
use AnimaID\Repositories\RoleRepository;
use AnimaID\Repositories\SpaceRepository;
use AnimaID\Repositories\UserRepository;
use AnimaID\Repositories\WikiRepository;
use AnimaID\Services\AnimatorService;
use AnimaID\Services\AttendanceService;
use AnimaID\Services\AuditService;
use AnimaID\Services\AuthService;
use AnimaID\Services\CalendarService;
use AnimaID\Services\ChildService;
use AnimaID\Services\CommunicationService;
use AnimaID\Services\MediaService;
use AnimaID\Services\PermissionService;
use AnimaID\Services\ReportService;
use AnimaID\Services\RoleService;
use AnimaID\Services\SpaceService;
use AnimaID\Services\UserService;
use AnimaID\Services\WikiService;
use AnimaID\Controllers\AnimatorController;
use AnimaID\Controllers\AttendanceController;
use AnimaID\Controllers\AuthController;
use AnimaID\Controllers\CalendarController;
use AnimaID\Controllers\ChildController;
use AnimaID\Controllers\CommunicationController;
use AnimaID\Controllers\MediaController;
use AnimaID\Controllers\ReportController;
use AnimaID\Controllers\RoleController;
use AnimaID\Controllers\SpaceController;
use AnimaID\Controllers\SystemController;
use AnimaID\Controllers\UserController;
use AnimaID\Controllers\WikiController;
use AnimaID\Middleware\AuditMiddleware;
use AnimaID\Middleware\AuthMiddleware;
use AnimaID\Middleware\CorsMiddleware;
use AnimaID\Middleware\PermissionMiddleware;
use AnimaID\Middleware\RateLimitMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

// Create Slim app
$app = AppFactory::create();

// Add routing middleware
$app->addRoutingMiddleware();

// Initialize config early so CorsMiddleware can be registered before other middleware
$config = ConfigManager::getInstance();

// CORS middleware - must be added before auth middleware so preflight requests
// are handled without requiring authentication
$app->add(new CorsMiddleware($config));

// Handle OPTIONS requests (CorsMiddleware returns 204 directly for OPTIONS)
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

// Initialize remaining dependencies
$dbFile = $config->get('database.file');
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Repositories
$userRepository          = new UserRepository($pdo);
$roleRepository          = new RoleRepository($pdo);
$permissionRepository    = new PermissionRepository($pdo);
$calendarRepository      = new CalendarRepository($pdo);
$childRepository         = new ChildRepository($pdo);
$wikiRepository          = new WikiRepository($pdo);
$spaceRepository         = new SpaceRepository($pdo);
$animatorRepository      = new AnimatorRepository($pdo);
$attendanceRepository    = new AttendanceRepository($pdo);
$communicationRepository = new CommunicationRepository($pdo);
$mediaRepository         = new MediaRepository($pdo);

// Services
$jwtManager = new JwtManager(
    $pdo,
    $config->get('jwt.secret'),
    $config->get('jwt.expiration_hours')
);
$authService          = new AuthService($userRepository, $jwtManager, $config, $pdo);
$userService          = new UserService($userRepository, $config, $pdo);
$permissionService    = new PermissionService($permissionRepository, $config, $pdo);
$calendarService      = new CalendarService($calendarRepository, $childRepository, $config);
$wikiService          = new WikiService($wikiRepository, $config);
$spaceService         = new SpaceService($spaceRepository, $config);
$childService         = new ChildService($childRepository, $config, $pdo);
$animatorService      = new AnimatorService($animatorRepository, $config, $pdo);
$attendanceService    = new AttendanceService($attendanceRepository, $config);
$communicationService = new CommunicationService($communicationRepository, $config);
$mediaService         = new MediaService($mediaRepository, $config);
$roleService          = new RoleService($roleRepository, $config, $pdo);
$reportService        = new ReportService($pdo);
$auditService         = new AuditService($pdo, $config);

// Controllers
$authController          = new AuthController($authService);
$userController          = new UserController($userService);
$calendarController      = new CalendarController($calendarService);
$systemController        = new SystemController($pdo);
$wikiController          = new WikiController($wikiService);
$spaceController         = new SpaceController($spaceService);
$childController         = new ChildController($childService);
$animatorController      = new AnimatorController($animatorService);
$attendanceController    = new AttendanceController($attendanceService);
$communicationController = new CommunicationController($communicationService);
$mediaController         = new MediaController($mediaService);
$roleController          = new RoleController($roleService);
$reportController        = new ReportController($reportService);

// Middleware
$authMiddleware = new AuthMiddleware($authService);

// Rate limit middleware - applied globally after auth middleware has run
$app->add(new RateLimitMiddleware($config, $pdo));

// Audit middleware - logs mutating requests; runs after auth so the user attribute is available
$app->add(new AuditMiddleware($auditService));

// Error handler that maps exception types to HTTP status codes
$errorMiddleware = $app->addErrorMiddleware($config->get('system.debug'), true, true);
$errorMiddleware->setDefaultErrorHandler(function ($request, $exception, $displayErrors) use ($app) {
    $httpCode = 500;
    if ($exception instanceof \AnimaID\Exceptions\NotFoundException) {
        $httpCode = 404;
    } elseif ($exception instanceof \AnimaID\Exceptions\ValidationException) {
        $httpCode = 422;
    } elseif ($exception instanceof \AnimaID\Exceptions\ForbiddenException) {
        $httpCode = 403;
    } elseif ($exception instanceof \AnimaID\Exceptions\ConflictException) {
        $httpCode = 409;
    } elseif ($exception instanceof \AnimaID\Exceptions\UnauthorizedException) {
        $httpCode = 401;
    }
    $response = $app->getResponseFactory()->createResponse($httpCode);
    $response->getBody()->write(json_encode(['success' => false, 'error' => $exception->getMessage()]));
    return $response->withHeader('Content-Type', 'application/json');
});

// ============================================================================
// PUBLIC ROUTES (No authentication required)
// ============================================================================

$app->post('/api/auth/login', [$authController, 'login']);

// Public communications listing
$app->get('/api/communications/public', [$communicationController, 'index']);

// Shared media resource (token-based, no auth)
$app->get('/api/media/shared/{token}', [$mediaController, 'getShared']);

// ============================================================================
// PROTECTED ROUTES (Authentication required)
// ============================================================================

$app->group('/api', function ($group) use (
    $authController,
    $userController,
    $calendarController,
    $systemController,
    $wikiController,
    $spaceController,
    $childController,
    $animatorController,
    $attendanceController,
    $communicationController,
    $mediaController,
    $reportController,
    $roleController,
    $permissionService
) {

    // Space Routes
    $group->group('/spaces', function ($group) use ($spaceController, $permissionService) {
        $group->get('', [$spaceController, 'index']);
        $group->get('/{id}', [$spaceController, 'show']);

        $group->post('', [$spaceController, 'create'])
             ->add(new PermissionMiddleware($permissionService, ['spaces.manage'], 'any'));

        $group->put('/{id}', [$spaceController, 'update'])
             ->add(new PermissionMiddleware($permissionService, ['spaces.manage'], 'any'));

        $group->delete('/{id}', [$spaceController, 'delete'])
             ->add(new PermissionMiddleware($permissionService, ['spaces.manage'], 'any'));

        $group->get('/{id}/bookings', [$spaceController, 'getBookings']);

        $group->post('/bookings', [$spaceController, 'createBooking'])
            ->add(new PermissionMiddleware($permissionService, ['spaces.book'], 'any'));

        $group->delete('/bookings/{id}', [$spaceController, 'deleteBooking'])
            ->add(new PermissionMiddleware($permissionService, ['spaces.manage'], 'any'));
    });

    // Wiki Routes
    $group->group('/wiki', function ($group) use ($wikiController, $permissionService) {
        $group->get('/pages', [$wikiController, 'index']);
        $group->get('/categories', [$wikiController, 'categories']);

        $group->post('/pages', [$wikiController, 'create'])
            ->add(new PermissionMiddleware($permissionService, ['wiki.create'], 'any'));

        $group->get('/pages/{id}', [$wikiController, 'show']);

        $group->put('/pages/{id}', [$wikiController, 'update'])
            ->add(new PermissionMiddleware($permissionService, ['wiki.edit'], 'any'));

        $group->delete('/pages/{id}', [$wikiController, 'delete'])
            ->add(new PermissionMiddleware($permissionService, ['wiki.moderate'], 'any'));
    });

    // System Status & Config
    $group->get('/system/status', [$systemController, 'status'])
        ->add(new PermissionMiddleware($permissionService, ['admin.system.view'], 'any'));

    $group->get('/system/config', [$systemController, 'config']);

    // Auth routes
    $group->post('/auth/logout', [$authController, 'logout']);
    $group->post('/auth/refresh', [$authController, 'refresh']);
    $group->get('/auth/me', [$authController, 'me']);

    // User routes (require admin permissions)
    $group->group('/users', function ($group) use ($userController) {
        $group->get('', [$userController, 'index']);
        $group->get('/stats', [$userController, 'stats']);
        $group->get('/{id}', [$userController, 'show']);
        $group->post('', [$userController, 'create']);
        $group->put('/{id}', [$userController, 'update']);
        $group->delete('/{id}', [$userController, 'delete']);
    })->add(new PermissionMiddleware($permissionService, ['admin.users', 'users.manage'], 'any'));

    // Role routes
    $group->group('/roles', function ($group) use ($roleController, $permissionService) {
        $group->get('', [$roleController, 'index']);
        $group->get('/{id}', [$roleController, 'show']);

        $group->post('', [$roleController, 'create'])
            ->add(new PermissionMiddleware($permissionService, ['admin.users'], 'any'));

        $group->put('/{id}', [$roleController, 'update'])
            ->add(new PermissionMiddleware($permissionService, ['admin.users'], 'any'));

        $group->delete('/{id}', [$roleController, 'delete'])
            ->add(new PermissionMiddleware($permissionService, ['admin.users'], 'any'));
    });

    // Calendar routes
    $group->group('/calendar', function ($group) use ($calendarController, $permissionService) {
        $group->get('', [$calendarController, 'index']);
        $group->post('', [$calendarController, 'create'])
            ->add(new PermissionMiddleware($permissionService, ['calendar.create'], 'any'));

        $group->get('/{id}', [$calendarController, 'show']);
        $group->put('/{id}', [$calendarController, 'update'])
            ->add(new PermissionMiddleware($permissionService, ['calendar.edit'], 'any'));

        $group->delete('/{id}', [$calendarController, 'delete'])
            ->add(new PermissionMiddleware($permissionService, ['calendar.delete'], 'any'));

        // Participants
        $group->get('/{id}/participants', [$calendarController, 'participants'])
            ->add(new PermissionMiddleware($permissionService, ['calendar.participants.view', 'calendar.view'], 'any'));

        $group->post('/{id}/register', [$calendarController, 'register'])
            ->add(new PermissionMiddleware($permissionService, ['calendar.participants.manage'], 'any'));

        $group->delete('/{id}/participants/{participantId}', [$calendarController, 'unregister'])
            ->add(new PermissionMiddleware($permissionService, ['calendar.participants.manage'], 'any'));
    });

    // Children routes
    $group->group('/children', function ($group) use ($childController) {
        $group->get('', [$childController, 'index']);
        $group->post('', [$childController, 'create']);
        $group->get('/{id}', [$childController, 'show']);
        $group->put('/{id}', [$childController, 'update']);
        $group->delete('/{id}', [$childController, 'delete']);
        $group->get('/{id}/guardians', [$childController, 'guardians']);
        $group->post('/{id}/guardians', [$childController, 'addGuardian']);
        $group->post('/{id}/notes', [$childController, 'addNote']);
    });

    // Animator routes
    $group->group('/animators', function ($group) use ($animatorController) {
        $group->get('', [$animatorController, 'index']);
        $group->post('', [$animatorController, 'create']);
        $group->get('/{id}', [$animatorController, 'show']);
        $group->put('/{id}', [$animatorController, 'update']);
        $group->delete('/{id}', [$animatorController, 'delete']);
        $group->post('/{id}/users', [$animatorController, 'linkUser']);
        $group->delete('/{id}/users/{userId}', [$animatorController, 'unlinkUser']);
        $group->post('/{id}/notes', [$animatorController, 'addNote']);
    });

    // Attendance routes
    $group->group('/attendance', function ($group) use ($attendanceController) {
        $group->get('', [$attendanceController, 'index']);
        $group->post('/checkin', [$attendanceController, 'checkIn']);
        $group->post('/{id}/checkout', [$attendanceController, 'checkOut']);
        $group->delete('/{id}', [$attendanceController, 'delete']);
    });

    // Reports routes
    $group->group('/reports', function ($group) use ($reportController) {
        $group->get('', [$reportController, 'index']);
        $group->get('/attendance', [$reportController, 'attendance']);
        $group->get('/children', [$reportController, 'children']);
        $group->get('/animators', [$reportController, 'animators']);
        $group->get('/summary', [$reportController, 'summary']);
    });

    // Communications routes (authenticated)
    $group->group('/communications', function ($group) use ($communicationController) {
        $group->get('', [$communicationController, 'index']);
        $group->post('', [$communicationController, 'create']);
        $group->get('/{id}', [$communicationController, 'show']);
        $group->put('/{id}', [$communicationController, 'update']);
        $group->delete('/{id}', [$communicationController, 'delete']);
        $group->get('/{id}/comments', [$communicationController, 'getComments']);
        $group->post('/{id}/comments', [$communicationController, 'addComment']);
    });

    // Media routes (authenticated)
    $group->group('/media', function ($group) use ($mediaController) {
        $group->get('', [$mediaController, 'index']);
        $group->post('', [$mediaController, 'upload']);
        $group->post('/share', [$mediaController, 'share']);
        $group->get('/files/{id}', [$mediaController, 'showFile']);
        $group->put('/files/{id}', [$mediaController, 'moveFile']);
        $group->delete('/files/{id}', [$mediaController, 'delete']);
        $group->get('/files/{id}/download', [$mediaController, 'download']);
        $group->get('/folders/{id}', [$mediaController, 'showFolder']);
        $group->post('/folders', [$mediaController, 'upload']);
        $group->put('/folders/{id}', [$mediaController, 'moveFolder']);
        $group->delete('/folders/{id}', [$mediaController, 'deleteFolder']);
    });

})->add($authMiddleware);

// Run app
$app->run();
