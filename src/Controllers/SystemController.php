<?php

namespace AnimaID\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AnimaID\Config\ConfigManager;
use PDO;

/**
 * System Controller
 * Handles system status and health checks
 */
class SystemController
{
    private PDO $pdo;
    private ConfigManager $config;

    public function __construct(PDO $pdo, ConfigManager $config)
    {
        $this->pdo    = $pdo;
        $this->config = $config;
    }

    /**
     * Get public config/feature flags
     * GET /api/system/config
     */
    public function config(Request $request, Response $response): Response
    {
        return $this->jsonResponse($response, [
            'success' => true,
            'config'  => [
                'features' => [
                    'show_medical_data' => true,
                ],
            ],
        ]);
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
     * List backups
     * GET /api/system/backups
     */
    public function listBackups(Request $request, Response $response): Response
    {
        $backupPath = $this->config->get('backup.path');

        if (!is_dir($backupPath)) {
            return $this->jsonResponse($response, ['success' => true, 'backups' => [], 'total_size_formatted' => '0 B']);
        }

        $files = glob($backupPath . '*.{db,sql,gz,zip}', GLOB_BRACE) ?: [];
        $backups = [];
        $totalBytes = 0;

        foreach ($files as $file) {
            $size = filesize($file);
            $totalBytes += $size;
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $backups[] = [
                'name'           => basename($file),
                'type'           => in_array($ext, ['db', 'sql']) ? 'database' : 'archive',
                'size_formatted' => $this->formatBytes($size),
                'created_at'     => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        usort($backups, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return $this->jsonResponse($response, [
            'success'             => true,
            'backups'             => $backups,
            'total_size_formatted' => $this->formatBytes($totalBytes),
        ]);
    }

    /**
     * Create a database backup
     * POST /api/system/backup
     */
    public function createBackup(Request $request, Response $response): Response
    {
        $backupPath = $this->config->get('backup.path');
        $dbFile     = $this->config->get('database.file');

        if (!is_dir($backupPath) && !mkdir($backupPath, 0755, true)) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Cannot create backup directory'], 500);
        }

        $filename   = 'backup_' . date('Y-m-d_H-i-s') . '.db';
        $dest       = $backupPath . $filename;
        $dbSuccess  = copy($dbFile, $dest);

        return $this->jsonResponse($response, [
            'success' => $dbSuccess,
            'results' => [
                'database' => [
                    'success'  => $dbSuccess,
                    'filename' => $dbSuccess ? $filename : null,
                    'error'    => $dbSuccess ? null : 'Failed to copy database file',
                ],
            ],
        ]);
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
                'status' => 'migrated',
                'details' => 'Fully migrated to Slim 4'
            ],
            [
                'name' => 'Spaces',
                'path' => '/api/spaces',
                'status' => 'migrated',
                'details' => 'Fully migrated to Slim 4'
            ],
            [
                'name' => 'Reports',
                'path' => '/api/reports',
                'status' => 'legacy',
                'details' => 'Running on legacy api/index.php'
            ]
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
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
