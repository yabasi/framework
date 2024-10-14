<?php

namespace Yabasi\Middleware;

use Closure;
use Yabasi\Api\RateLimiter;
use Yabasi\Http\Request;
use Yabasi\Http\Response;

class RateLimitMiddleware implements MiddlewareInterface
{
    protected $rateLimiter;

    public function __construct(RateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->rateLimiter->tooManyAttempts($key, 60, 1)) {
            return $this->buildResponse($key, 60);
        }

        $this->rateLimiter->hit($key, 1);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $this->calculateRemainingAttempts($key, 60)
        );
    }

    protected function resolveRequestSignature($request)
    {
        return sha1(
            $request->getMethod() .
            '|' . $request->getUri() .
            '|' . $request->getClientIp()
        );
    }

    protected function buildResponse($key, $maxAttempts)
    {
        $response = new Response('Too Many Attempts.', 429);

        $retryAfter = $this->getTimeUntilNextRetry($key);

        return $this->addHeaders(
            $response,
            $this->calculateRemainingAttempts($key, $maxAttempts, $retryAfter),
            $retryAfter
        );
    }

    protected function addHeaders(Response $response, $remainingAttempts, $retryAfter = null)
    {
        $response->setHeader('X-RateLimit-Limit', 60);
        $response->setHeader('X-RateLimit-Remaining', $remainingAttempts);

        if (!is_null($retryAfter)) {
            $response->setHeader('Retry-After', $retryAfter);
            $response->setHeader('X-RateLimit-Reset', time() + $retryAfter);
        }

        return $response;
    }

    protected function calculateRemainingAttempts($key, $maxAttempts, $retryAfter = null)
    {
        if (is_null($retryAfter)) {
            return $maxAttempts - $this->rateLimiter->attempts($key) + 1;
        }

        return 0;
    }

    protected function getTimeUntilNextRetry($key)
    {
        $availableAt = $this->rateLimiter->cache->get($key . ':timer');
        return $availableAt ? $availableAt - time() : 0;
    }
}