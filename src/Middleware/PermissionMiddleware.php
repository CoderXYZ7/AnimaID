<?php

namespace AnimaID\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use AnimaID\Services\PermissionService;
use Slim\Psr7\Response as SlimResponse;

/**
 * Permission Middleware
 * Checks if user has required permissions
 */
class PermissionMiddleware
{
    private PermissionService $permissionService;
    private array $requiredPermissions;
    private string $mode; // 'any' or 'all'

    public function __construct(
        PermissionService $permissionService,
        array $requiredPermissions,
        string $mode = 'any'
    ) {
        $this->permissionService = $permissionService;
        $this->requiredPermissions = $requiredPermissions;
        $this->mode = $mode;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Get user from request (set by AuthMiddleware)
        $user = $request->getAttribute('user');

        if (!$user) {
            return $this->forbiddenResponse('User not authenticated');
        }

        $userId = $user['id'];

        // Check permissions based on mode
        $hasPermission = false;

        if ($this->mode === 'all') {
            $hasPermission = $this->permissionService->checkAllPermissions($userId, $this->requiredPermissions);
        } else {
            $hasPermission = $this->permissionService->checkAnyPermission($userId, $this->requiredPermissions);
        }

        if (!$hasPermission) {
            return $this->forbiddenResponse(
                'Insufficient permissions. Required: ' . implode(', ', $this->requiredPermissions)
            );
        }

        // Continue to next middleware/controller
        return $handler->handle($request);
    }

    private function forbiddenResponse(string $message): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => $message
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
    }

    /**
     * Factory method to create middleware requiring ANY of the permissions
     */
    public static function any(PermissionService $permissionService, array $permissions): self
    {
        return new self($permissionService, $permissions, 'any');
    }

    /**
     * Factory method to create middleware requiring ALL of the permissions
     */
    public static function all(PermissionService $permissionService, array $permissions): self
    {
        return new self($permissionService, $permissions, 'all');
    }
}
