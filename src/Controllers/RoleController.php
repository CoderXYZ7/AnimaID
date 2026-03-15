<?php

namespace AnimaID\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AnimaID\Services\RoleService;

/**
 * Role Controller
 * Handles role management endpoints
 */
class RoleController
{
    private RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * List all roles
     * GET /api/roles
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $roles = $this->roleService->getAllRoles();

            return $this->jsonResponse($response, [
                'success' => true,
                'roles' => $roles
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single role with its permissions
     * GET /api/roles/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $roleId = (int) $args['id'];
            $role = $this->roleService->getRoleById($roleId);

            if (!$role) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Role not found'
                ], 404);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'role' => $role
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new role
     * POST /api/roles
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);

            $role = $this->roleService->createRole($data);

            return $this->jsonResponse($response, [
                'success' => true,
                'role' => $role,
                'message' => 'Role created successfully'
            ], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update a role
     * PUT /api/roles/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $roleId = (int) $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);

            $role = $this->roleService->updateRole($roleId, $data);

            return $this->jsonResponse($response, [
                'success' => true,
                'role' => $role,
                'message' => 'Role updated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete a role
     * DELETE /api/roles/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $roleId = (int) $args['id'];

            $this->roleService->deleteRole($roleId);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
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
