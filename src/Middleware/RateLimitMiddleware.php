<?php

namespace AnimaID\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use AnimaID\Config\ConfigManager;
use Slim\Psr7\Response as SlimResponse;
use PDO;

/**
 * Rate Limit Middleware
 * Enforces per-identifier request limits over a sliding time window.
 *
 * Limits (configurable via ConfigManager):
 *   - Authenticated users:  api.rate_limit_authenticated  (default 1000/hour)
 *   - Unauthenticated:      api.rate_limit_unauthenticated (default 100/hour)
 *   - Login endpoint:       security.max_login_attempts    (default 5) then
 *                           security.lockout_duration_minutes lockout (default 15)
 */
class RateLimitMiddleware
{
    private ConfigManager $config;
    private PDO $db;

    public function __construct(ConfigManager $config, PDO $db)
    {
        $this->config = $config;
        $this->db = $db;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Periodically clean up stale entries (entries older than 2 hours)
        $this->cleanup();

        $method = $request->getMethod();
        $path   = $request->getUri()->getPath();

        // Determine whether this is the login endpoint
        $isLoginEndpoint = ($method === 'POST' && $path === '/api/auth/login');

        // Build the identifier
        $ip   = $this->resolveIp($request);
        $user = $request->getAttribute('user');

        if ($isLoginEndpoint) {
            $body     = json_decode((string) $request->getBody(), true) ?? [];
            $username = $body['username'] ?? '';
            $identifier = "login:{$ip}:{$username}";
        } elseif ($user !== null) {
            $identifier = 'user:' . $user['id'];
        } else {
            $identifier = "ip:{$ip}";
        }

        // Fetch or initialise the attempt record
        $record = $this->fetchRecord($identifier);

        $now = date('Y-m-d H:i:s');

        if ($isLoginEndpoint) {
            return $this->handleLoginRateLimit($request, $handler, $identifier, $record, $now);
        }

        return $this->handleGeneralRateLimit($request, $handler, $identifier, $record, $now, $user !== null);
    }

    // -------------------------------------------------------------------------
    // Login-specific rate limiting (lockout-based)
    // -------------------------------------------------------------------------

    private function handleLoginRateLimit(
        Request $request,
        RequestHandler $handler,
        string $identifier,
        ?array $record,
        string $now
    ): Response {
        $maxAttempts      = (int) $this->config->get('security.max_login_attempts', 5);
        $lockoutMinutes   = (int) $this->config->get('security.lockout_duration_minutes', 15);

        // Check whether the identifier is currently locked out
        if ($record && $record['locked_until'] !== null && $record['locked_until'] > $now) {
            $retryAfter = (int) ceil(
                (strtotime($record['locked_until']) - time()) / 60
            );
            return $this->tooManyRequestsResponse($retryAfter);
        }

        // If outside the current 1-hour window, reset the counter
        if ($record && $this->windowExpired($record['window_start'])) {
            $this->deleteRecord($identifier);
            $record = null;
        }

        if (!$record) {
            $this->createRecord($identifier, $now);
            return $handler->handle($request);
        }

        // Increment and check
        $attempts = $record['attempts'] + 1;
        $lockedUntil = null;

        if ($attempts >= $maxAttempts) {
            $lockedUntil = date('Y-m-d H:i:s', strtotime("+{$lockoutMinutes} minutes"));
        }

        $this->updateRecord($identifier, $attempts, $lockedUntil);

        if ($lockedUntil !== null) {
            return $this->tooManyRequestsResponse($lockoutMinutes);
        }

        return $handler->handle($request);
    }

    // -------------------------------------------------------------------------
    // General rate limiting (window-based)
    // -------------------------------------------------------------------------

    private function handleGeneralRateLimit(
        Request $request,
        RequestHandler $handler,
        string $identifier,
        ?array $record,
        string $now,
        bool $isAuthenticated
    ): Response {
        $limit = $isAuthenticated
            ? (int) $this->config->get('api.rate_limit_authenticated', 1000)
            : (int) $this->config->get('api.rate_limit_unauthenticated', 100);

        // If outside the current 1-hour window, reset the counter
        if ($record && $this->windowExpired($record['window_start'])) {
            $this->deleteRecord($identifier);
            $record = null;
        }

        if (!$record) {
            $this->createRecord($identifier, $now);
            return $handler->handle($request);
        }

        $attempts = $record['attempts'] + 1;

        if ($attempts > $limit) {
            // Calculate seconds until the current window expires
            $windowEnd  = strtotime($record['window_start']) + 3600;
            $retryAfter = max(1, (int) ceil(($windowEnd - time()) / 60));
            return $this->tooManyRequestsResponse($retryAfter);
        }

        $this->updateRecord($identifier, $attempts, null);

        return $handler->handle($request);
    }

    // -------------------------------------------------------------------------
    // Database helpers
    // -------------------------------------------------------------------------

    private function fetchRecord(string $identifier): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM rate_limit_attempts WHERE identifier = ? LIMIT 1'
        );
        $stmt->execute([$identifier]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function createRecord(string $identifier, string $now): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO rate_limit_attempts (identifier, attempts, window_start)
             VALUES (?, 1, ?)'
        );
        $stmt->execute([$identifier, $now]);
    }

    private function updateRecord(string $identifier, int $attempts, ?string $lockedUntil): void
    {
        $stmt = $this->db->prepare(
            'UPDATE rate_limit_attempts
             SET attempts = ?, locked_until = ?
             WHERE identifier = ?'
        );
        $stmt->execute([$attempts, $lockedUntil, $identifier]);
    }

    private function deleteRecord(string $identifier): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM rate_limit_attempts WHERE identifier = ?'
        );
        $stmt->execute([$identifier]);
    }

    private function cleanup(): void
    {
        $this->db->exec(
            "DELETE FROM rate_limit_attempts
             WHERE window_start < datetime('now', '-2 hours')"
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function windowExpired(string $windowStart): bool
    {
        return (time() - strtotime($windowStart)) >= 3600;
    }

    private function resolveIp(Request $request): string
    {
        $serverParams = $request->getServerParams();

        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($serverParams[$key])) {
                // X-Forwarded-For may contain a comma-separated list; take the first
                return trim(explode(',', $serverParams[$key])[0]);
            }
        }

        return 'unknown';
    }

    private function tooManyRequestsResponse(int $retryAfterMinutes): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode([
            'success'     => false,
            'error'       => 'Too many requests',
            'retry_after' => $retryAfterMinutes,
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Retry-After', (string) ($retryAfterMinutes * 60))
            ->withStatus(429);
    }
}
