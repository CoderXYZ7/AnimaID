<?php

namespace AnimaID\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use AnimaID\Services\AuditService;

/**
 * Audit Middleware
 * Logs mutating requests (POST, PUT, DELETE) to the audit log after they have
 * been handled. The response passes through unchanged.
 */
class AuditMiddleware
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Let the request be handled first so the response is always passed through.
        $response = $handler->handle($request);

        $method = strtoupper($request->getMethod());

        // Only audit mutating methods.
        if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
            return $response;
        }

        // Derive an action string from HTTP method + path.
        // E.g. POST /api/children  →  "create.children"
        //      PUT  /api/children/5 → "update.children"
        //      DELETE /api/children/5 → "delete.children"
        $path     = $request->getUri()->getPath();
        $action   = $this->resolveAction($method, $path);
        $resource = $this->resolveResource($path);

        // User injected by AuthMiddleware.
        $user   = $request->getAttribute('user');
        $userId = is_array($user) && isset($user['id']) ? (int) $user['id'] : null;

        // IP address – respect common proxy headers.
        $serverParams = $request->getServerParams();
        $ipAddress    = $serverParams['HTTP_X_FORWARDED_FOR']
            ?? $serverParams['HTTP_X_REAL_IP']
            ?? $serverParams['REMOTE_ADDR']
            ?? '';

        // Only take the first IP if a comma-separated list is present.
        if (str_contains($ipAddress, ',')) {
            $ipAddress = trim(explode(',', $ipAddress)[0]);
        }

        $userAgent = $request->getHeaderLine('User-Agent');

        // Build context from parsed request body (arrays/objects only).
        $context = [];
        $body    = $request->getParsedBody();
        if (is_array($body)) {
            // Strip sensitive fields before storing.
            $sensitive = ['password', 'password_hash', 'token', 'secret'];
            foreach ($sensitive as $key) {
                unset($body[$key]);
            }
            $context = $body;
        }

        $this->auditService->log(
            $userId,
            $action,
            $resource,
            null,
            $context,
            $ipAddress,
            $userAgent
        );

        return $response;
    }

    /**
     * Map HTTP method to an action verb.
     * POST   → create
     * PUT    → update
     * DELETE → delete
     */
    private function resolveAction(string $method, string $path): string
    {
        $verbMap = [
            'POST'   => 'create',
            'PUT'    => 'update',
            'DELETE' => 'delete',
        ];

        $verb     = $verbMap[$method] ?? strtolower($method);
        $resource = $this->resolveResource($path);

        return $resource !== '' ? "{$verb}.{$resource}" : $verb;
    }

    /**
     * Extract the primary resource name from a URL path.
     * "/api/children/5/notes" → "children"
     * "/api/animators"        → "animators"
     */
    private function resolveResource(string $path): string
    {
        // Remove leading /api/ prefix (or any leading slashes).
        $stripped = ltrim(preg_replace('#^/api/#', '', $path), '/');

        // The first path segment is the resource name.
        $parts = explode('/', $stripped);

        return $parts[0] ?? '';
    }
}
