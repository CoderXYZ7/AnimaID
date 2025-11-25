<?php

namespace AnimaID\Services;

use AnimaID\Repositories\UserRepository;
use AnimaID\Security\JwtManager;
use AnimaID\Config\ConfigManager;
use PDO;

/**
 * Authentication Service
 * Handles login, logout, token management, and session handling
 */
class AuthService
{
    private UserRepository $userRepository;
    private JwtManager $jwtManager;
    private ConfigManager $config;
    private PDO $db;

    public function __construct(
        UserRepository $userRepository,
        JwtManager $jwtManager,
        ConfigManager $config,
        PDO $db
    ) {
        $this->userRepository = $userRepository;
        $this->jwtManager = $jwtManager;
        $this->config = $config;
        $this->db = $db;
    }

    /**
     * Authenticate user and return JWT token
     */
    public function login(string $username, string $password): array
    {
        // Find user by username
        $user = $this->userRepository->findByUsername($username);

        if (!$user) {
            throw new \Exception('Invalid credentials');
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            throw new \Exception('Invalid credentials');
        }

        // Check if user is active
        if (!$user['is_active']) {
            throw new \Exception('Account is disabled');
        }

        // Get user roles
        $userWithRoles = $this->userRepository->findWithRoles($user['id']);
        $roles = array_column($userWithRoles['roles'], 'name');

        // Generate JWT token
        $tokenData = $this->jwtManager->generateToken(
            $user['id'],
            $user['username'],
            $roles
        );

        // Update last login
        $this->userRepository->updateLastLogin($user['id']);

        return [
            'success' => true,
            'token' => $tokenData['token'],
            'expires_at' => $tokenData['expires_at'],
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'roles' => $roles
            ]
        ];
    }

    /**
     * Verify JWT token and return user data
     */
    public function verifyToken(string $token): ?array
    {
        $decoded = $this->jwtManager->validateToken($token);

        if (!$decoded) {
            return null;
        }

        // Get fresh user data
        $user = $this->userRepository->findById($decoded->sub);

        if (!$user || !$user['is_active']) {
            return null;
        }

        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name']
        ];
    }

    /**
     * Logout user by revoking token
     */
    public function logout(string $token): bool
    {
        return $this->jwtManager->revokeToken($token);
    }

    /**
     * Refresh token (generate new token)
     */
    public function refreshToken(string $oldToken): array
    {
        $decoded = $this->jwtManager->validateToken($oldToken);

        if (!$decoded) {
            throw new \Exception('Invalid token');
        }

        // Revoke old token
        $this->jwtManager->revokeToken($oldToken);

        // Get user data
        $user = $this->userRepository->findWithRoles($decoded->sub);

        if (!$user || !$user['is_active']) {
            throw new \Exception('User not found or inactive');
        }

        $roles = array_column($user['roles'], 'name');

        // Generate new token
        $tokenData = $this->jwtManager->generateToken(
            $user['id'],
            $user['username'],
            $roles
        );

        return [
            'success' => true,
            'token' => $tokenData['token'],
            'expires_at' => $tokenData['expires_at']
        ];
    }

    /**
     * Get current user from token
     */
    public function getCurrentUser(string $token): ?array
    {
        $decoded = $this->jwtManager->validateToken($token);

        if (!$decoded) {
            return null;
        }

        return $this->userRepository->findWithRoles($decoded->sub);
    }

    /**
     * Get active sessions for user
     */
    public function getActiveSessions(int $userId): array
    {
        return $this->userRepository->getActiveSessions($userId);
    }

    /**
     * Revoke all sessions for user
     */
    public function revokeAllSessions(int $userId): bool
    {
        $sessions = $this->userRepository->getActiveSessions($userId);

        foreach ($sessions as $session) {
            $this->jwtManager->revokeToken($session['session_token']);
        }

        return true;
    }
}
