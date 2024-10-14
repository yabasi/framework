<?php

namespace Yabasi\Middleware;

use Closure;
use Yabasi\Config\Config;
use Yabasi\Http\Request;
use Yabasi\Http\Response;

class CorsMiddleware implements MiddlewareInterface
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $corsConfig = $this->config->get('cors', []);

        $response->setHeader('Access-Control-Allow-Origin', $this->getAllowedOriginHeader($request, $corsConfig['allowed_origins'] ?? ['*']));
        $response->setHeader('Access-Control-Allow-Methods', implode(', ', $corsConfig['allowed_methods'] ?? ['*']));
        $response->setHeader('Access-Control-Allow-Headers', implode(', ', $corsConfig['allowed_headers'] ?? ['*']));

        if ($corsConfig['allow_credentials'] ?? false) {
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }

        if ($maxAge = $corsConfig['max_age'] ?? 0) {
            $response->setHeader('Access-Control-Max-Age', $maxAge);
        }

        if ($request->getMethod() === 'OPTIONS') {
            $response->setStatusCode(204);
            $response->setContent('');
        }

        return $response;
    }

    protected function getAllowedOriginHeader(Request $request, array $allowedOrigins): string
    {
        if (in_array('*', $allowedOrigins)) {
            return '*';
        }

        $origin = $request->header('Origin');
        if (in_array($origin, $allowedOrigins)) {
            return $origin;
        }

        return '';
    }
}