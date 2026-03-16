<?php

namespace AnimaID\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AnimaID\Services\PermissionService;

/**
 * Permission Controller
 * Handles permission listing endpoints
 */
class PermissionController
{
    private PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * List all permissions grouped by module
     * GET /api/permissions
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $grouped = $this->permissionService->getAllGroupedByModule();

            return $this->jsonResponse($response, [
                'success'     => true,
                'permissions' => $grouped,
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
