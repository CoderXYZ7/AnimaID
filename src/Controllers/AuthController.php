<?php

namespace AnimaID\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AnimaID\Services\AuthService;

/**
 * Authentication Controller
 * Handles authentication endpoints
 */
class AuthController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Login endpoint
     * POST /api/auth/login
     */
    public function login(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);

            if (empty($data['username']) || empty($data['password'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Username and password are required'
                ], 400);
            }

            $result = $this->authService->login($data['username'], $data['password']);

            return $this->jsonResponse($response, $result);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Logout endpoint
     * POST /api/auth/logout
     */
    public function logout(Request $request, Response $response): Response
    {
        try {
            $token = $request->getAttribute('token');

            $this->authService->logout($token);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Logged out successfully'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh token endpoint
     * POST /api/auth/refresh
     */
    public function refresh(Request $request, Response $response): Response
    {
        try {
            $token = $request->getAttribute('token');

            $result = $this->authService->refreshToken($token);

            return $this->jsonResponse($response, $result);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Get current user endpoint
     * GET /api/auth/me
     */
    public function me(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');

            return $this->jsonResponse($response, [
                'success' => true,
                'user' => $user
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
