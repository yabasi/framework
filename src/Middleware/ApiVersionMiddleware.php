<?php

namespace Yabasi\Middleware;

use Closure;
use Yabasi\Http\Request;
use Yabasi\Http\Response;

class ApiVersionMiddleware implements MiddlewareInterface
{
    protected $defaultVersion = 'v1';

    public function handle(Request $request, Closure $next): Response
    {
        $version = $this->getVersion($request);
        $request->setAttribute('api_version', $version);

        return $next($request);
    }

    protected function getVersion(Request $request)
    {
        $version = $request->getHeader('Accept-Version');

        if (!$version) {
            $version = $this->extractVersionFromUrl($request->getUri());
        }

        return $version ?: $this->defaultVersion;
    }

    protected function extractVersionFromUrl($url)
    {
        if (preg_match('#/v(\d+)/#', $url, $matches)) {
            return 'v' . $matches[1];
        }

        return null;
    }
}