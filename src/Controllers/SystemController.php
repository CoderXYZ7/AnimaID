<?php

namespace AnimaID\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

/**
 * System Controller
 * Handles system status and health checks
 */
class SystemController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get system status
     * GET /api/system/status
     */
    public function status(Request $request, Response $response): Response
    {
        $status = [
            'status' => 'healthy',
            'version' => '1.0.0', // Could be read from a config or version file
            'environment' => getenv('APP_ENV') ?: 'development',
            'php_version' => PHP_VERSION,
            'timestamp' => date('c'),
            'database' => $this->checkDatabase(),
            'modules' => $this->getModuleStatus()
        ];

        return $this->jsonResponse($response, $status);
    }

    /**
     * Check database connection
     */
    private function checkDatabase(): bool
    {
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get status of API modules
     */
    private function getModuleStatus(): array
    {
        // manually curation of status based on codebase state
        return [
            [
                'name' => 'Authentication',
                'path' => '/api/auth',
                'status' => 'migrated', 
                'details' => 'Fully migrated to Slim 4 + JWT'
            ],
            [
                'name' => 'User Management',
                'path' => '/api/users',
                'status' => 'migrated',
                'details' => 'Fully migrated to Slim 4'
            ],
            [
                'name' => 'Role Management',
                'path' => '/api/roles', // Implemented in repo/service but routes not explicit in index-new yet, let's check index-new
                'status' => 'partial', // Checking index-new, I don't see /api/roles group yet! only /api/users
                'details' => 'Repository and Service exist, routes pending'
            ],
            [
                'name' => 'Calendar',
                'path' => '/api/calendar',
                'status' => 'migrated',
                'details' => 'Fully migrated to Slim 4'
            ],
            [
                'name' => 'Attendance',
                'path' => '/api/attendance',
                'status' => 'legacy',
                'details' => 'Running on legacy api/index.php'
            ],
            [
                'name' => 'Wiki',
                'path' => '/api/wiki',
                'status' => 'legacy',
                'details' => 'Running on legacy api/index.php'
            ],
            [
                'name' => 'Spaces',
                'path' => '/api/spaces',
                'status' => 'legacy',
                'details' => 'Running on legacy api/index.php'
            ],
            [
                'name' => 'Reports',
                'path' => '/api/reports',
                'status' => 'legacy',
                'details' => 'Running on legacy api/index.php'
            ]
        ];
    }

    /**
     * Helper method to create JSON response
     */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
