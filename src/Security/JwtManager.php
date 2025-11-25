<?php

namespace AnimaID\Security;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Exception;

/**
 * JWT Manager
 * Handles JWT token generation, validation, and revocation using firebase/php-jwt
 */
class JwtManager
{
    private string $secret;
    private string $algorithm = 'HS256';
    private int $expirationHours;
    private \PDO $db;

    public function __construct(\PDO $db, string $secret, int $expirationHours = 2)
    {
        $this->db = $db;
        $this->secret = $secret;
        $this->expirationHours = $expirationHours;
    }

    /**
     * Generate a JWT token for a user
     */
    public function generateToken(int $userId, string $username, array $roles = []): array
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + ($this->expirationHours * 3600);

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'sub' => $userId,
            'username' => $username,
            'roles' => $roles
        ];

        $token = FirebaseJWT::encode($payload, $this->secret, $this->algorithm);

        // Store session in database
        $this->storeSession($userId, $token, $expiresAt);

        return [
            'token' => $token,
            'expires_at' => date('Y-m-d\TH:i:s\Z', $expiresAt)
        ];
    }

    /**
     * Validate and decode a JWT token
     */
    public function validateToken(string $token): ?object
    {
        try {
            // Check if token is blacklisted
            if ($this->isTokenBlacklisted($token)) {
                throw new Exception('Token has been revoked');
            }

            // Decode and validate token
            $decoded = FirebaseJWT::decode($token, new Key($this->secret, $this->algorithm));

            // Verify session exists and is not expired
            if (!$this->isSessionValid($token)) {
                throw new Exception('Session invalid or expired');
            }

            return $decoded;
        } catch (Exception $e) {
            error_log('JWT validation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Revoke a token by adding it to blacklist
     */
    public function revokeToken(string $token): bool
    {
        try {
            $decoded = FirebaseJWT::decode($token, new Key($this->secret, $this->algorithm));
            
            $stmt = $this->db->prepare("
                INSERT INTO token_blacklist (token, user_id, revoked_at, expires_at)
                VALUES (?, ?, datetime('now'), datetime(?, 'unixepoch'))
            ");
            
            $stmt->execute([
                $token,
                $decoded->sub,
                $decoded->exp
            ]);

            // Also remove from active sessions
            $this->removeSession($token);

            return true;
        } catch (Exception $e) {
            error_log('Token revocation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if token is blacklisted
     */
    private function isTokenBlacklisted(string $token): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM token_blacklist 
            WHERE token = ? AND expires_at > datetime('now')
        ");
        $stmt->execute([$token]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }

    /**
     * Store session in database
     */
    private function storeSession(int $userId, string $token, int $expiresAt): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO user_sessions (user_id, session_token, expires_at, ip_address, user_agent)
            VALUES (?, ?, datetime(?, 'unixepoch'), ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $token,
            $expiresAt,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    /**
     * Check if session is valid
     */
    private function isSessionValid(string $token): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM user_sessions 
            WHERE session_token = ? AND expires_at > datetime('now')
        ");
        $stmt->execute([$token]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }

    /**
     * Remove session from database
     */
    private function removeSession(string $token): void
    {
        $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE session_token = ?");
        $stmt->execute([$token]);
    }

    /**
     * Clean up expired tokens and sessions
     */
    public function cleanupExpired(): void
    {
        // Remove expired blacklisted tokens
        $this->db->exec("DELETE FROM token_blacklist WHERE expires_at < datetime('now')");
        
        // Remove expired sessions
        $this->db->exec("DELETE FROM user_sessions WHERE expires_at < datetime('now')");
    }
}
