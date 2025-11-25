<?php

namespace AnimaID\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AnimaID\Services\UserService;

/**
 * User Controller
 * Handles user management endpoints
 */
class UserController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * List users
     * GET /api/users
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $page = (int) ($params['page'] ?? 1);
            $limit = (int) ($params['limit'] ?? 20);
            $search = $params['search'] ?? '';

            $result = $this->userService->getUsers($page, $limit, $search);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $result['users'],
                'pagination' => $result['pagination']
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single user
     * GET /api/users/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = (int) $args['id'];
            $user = $this->userService->getUserById($userId);

            if (!$user) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create user
     * POST /api/users
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            $currentUser = $request->getAttribute('user');
            
            $data['created_by'] = $currentUser['id'];

            $user = $this->userService->createUser($data);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $user,
                'message' => 'User created successfully'
            ], 201);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update user
     * PUT /api/users/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = (int) $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);

            $user = $this->userService->updateUser($userId, $data);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $user,
                'message' => 'User updated successfully'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete user
     * DELETE /api/users/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = (int) $args['id'];

            $this->userService->deleteUser($userId);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get user statistics
     * GET /api/users/stats
     */
    public function stats(Request $request, Response $response): Response
    {
        try {
            $stats = $this->userService->getStatistics();

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
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
