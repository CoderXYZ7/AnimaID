<?php

/**
 * AnimaID API - New Slim-based Router
 * Modern routing using Slim Framework with proper controllers and middleware
 */

use Slim\Factory\AppFactory;
use AnimaID\Config\ConfigManager;
use AnimaID\Security\JwtManager;
use AnimaID\Repositories\UserRepository;
use AnimaID\Repositories\RoleRepository;
use AnimaID\Repositories\PermissionRepository;
use AnimaID\Services\AuthService;
use AnimaID\Services\UserService;
use AnimaID\Services\PermissionService;
use AnimaID\Controllers\AuthController;
use AnimaID\Controllers\UserController;
use AnimaID\Controllers\SystemController;
use AnimaID\Middleware\AuthMiddleware;
use AnimaID\Repositories\CalendarRepository;
use AnimaID\Repositories\ChildRepository;
use AnimaID\Services\CalendarService;
use AnimaID\Controllers\CalendarController;
use AnimaID\Middleware\PermissionMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

// Create Slim app
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add routing middleware
$app->addRoutingMiddleware();

// CORS middleware
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Handle OPTIONS requests
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

// Initialize dependencies
$config = ConfigManager::getInstance();
$dbFile = $config->get('database.file');
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Repositories
$userRepository = new UserRepository($pdo);
$roleRepository = new RoleRepository($pdo);
$permissionRepository = new PermissionRepository($pdo);
$calendarRepository = new CalendarRepository($pdo);
$childRepository = new ChildRepository($pdo);

// Services
$jwtManager = new JwtManager(
    $pdo,
    $config->get('jwt.secret'),
    $config->get('jwt.expiration_hours')
);
$authService = new AuthService($userRepository, $jwtManager, $config, $pdo);
$userService = new UserService($userRepository, $config, $pdo);
$permissionService = new PermissionService($permissionRepository, $config, $pdo);
$calendarService = new CalendarService($calendarRepository, $childRepository, $config);

// Controllers
$authController = new AuthController($authService);
$userController = new UserController($userService);
$calendarController = new CalendarController($calendarService);
$systemController = new SystemController($pdo);

// Middleware
$authMiddleware = new AuthMiddleware($authService);

// ============================================================================
// PUBLIC ROUTES (No authentication required)
// ============================================================================

$app->post('/api/auth/login', [$authController, 'login']);

// ============================================================================
// PROTECTED ROUTES (Authentication required)
// ============================================================================

$app->group('/api', function ($group) use ($authController, $userController, $calendarController, $systemController, $permissionService) {
    
    // System Status
    $group->get('/system/status', [$systemController, 'status'])
        ->add(new PermissionMiddleware($permissionService, ['admin.system.view'], 'any'));
    
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

})->add($authMiddleware);

// ============================================================================
// FALLBACK - Redirect to old API for endpoints not yet migrated
// ============================================================================

$app->any('/api/{routes:.+}', function ($request, $response) {
    // For now, return a message indicating the endpoint is not yet migrated
    $response->getBody()->write(json_encode([
        'success' => false,
        'error' => 'This endpoint has not been migrated to the new API yet. Please use the legacy API.',
        'legacy_api' => '/api/index.php (old)'
    ]));
    
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(501); // Not Implemented
});

// Run app
$app->run();
