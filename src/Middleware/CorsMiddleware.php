<?php

namespace AnimaID\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use AnimaID\Config\ConfigManager;
use Slim\Psr7\Response as SlimResponse;

/**
 * CORS Middleware
 * Validates the incoming Origin header against the configured allowed origins list
 * and sets the appropriate Access-Control-Allow-Origin response header.
 */
class CorsMiddleware
{
    private ConfigManager $config;

    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $origin = $request->getHeaderLine('Origin');

        // Retrieve allowed origins from config (stored as array after explode in ConfigManager)
        $allowedOrigins = $this->config->get('api.cors_origins', []);
        if (is_string($allowedOrigins)) {
            $allowedOrigins = array_filter(array_map('trim', explode(',', $allowedOrigins)));
        }

        $originAllowed = $origin !== '' && in_array($origin, $allowedOrigins, true);

        // Handle preflight OPTIONS requests immediately
        if ($request->getMethod() === 'OPTIONS') {
            $response = new SlimResponse();
            if ($originAllowed) {
                $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
            }
            return $response
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->withHeader('Access-Control-Max-Age', '86400')
                ->withStatus(204);
        }

        $response = $handler->handle($request);

        if ($originAllowed) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        }

        return $response
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    }
}
