<?php

namespace Yabasi\Middleware;

use Closure;
use Yabasi\Http\Request;
use Yabasi\Http\Response;
use Yabasi\Security\CsrfProtection;

class CsrfMiddleware implements MiddlewareInterface
{
    protected CsrfProtection $csrfProtection;

    public function __construct(CsrfProtection $csrfProtection)
    {
        $this->csrfProtection = $csrfProtection;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldCheckCsrf($request)) {
            $token = $this->csrfProtection->getTokenFromRequest($request);

            if (!$token || !$this->csrfProtection->validateToken($token)) {
                return new Response('CSRF token mismatch', 403);
            }

            $this->csrfProtection->refreshToken();
        }

        $response = $next($request);
        $this->addCsrfTokenToResponse($response);

        return $response;
    }

    protected function shouldCheckCsrf(Request $request): bool
    {
        $excludedMethods = ['GET', 'HEAD', 'OPTIONS'];
        return !in_array($request->getMethod(), $excludedMethods);
    }

    protected function addCsrfTokenToResponse(Response $response): void
    {
        $response->setHeader('X-CSRF-TOKEN', $this->csrfProtection->generateToken());
    }
}