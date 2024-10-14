<?php

namespace Yabasi\Routing;

use Yabasi\Middleware\ApiVersionMiddleware;
use Yabasi\Middleware\RateLimitMiddleware;

class ApiRouter
{
    protected Router $router;
    protected string $apiPrefix = 'api';
    protected string $version = 'v1';

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function group(array $attributes, \Closure $callback): void
    {
        $prefix = $this->apiPrefix . '/' . $this->version;
        $attributes['prefix'] = isset($attributes['prefix']) ? $prefix . '/' . $attributes['prefix'] : $prefix;

        $attributes['middleware'] = array_merge(
            $attributes['middleware'] ?? [],
            [RateLimitMiddleware::class, ApiVersionMiddleware::class]
        );

        $this->router->group($attributes, $callback);
    }

    public function resource($name, $controller, $middleware = []): void
    {
        $prefix = $this->apiPrefix . '/' . $this->version;
        $fullPrefix = $prefix . '/' . $name;

        $this->router->get($fullPrefix, $controller . '@index', $middleware);
        $this->router->get($fullPrefix . '/{id}', $controller . '@show', $middleware);
        $this->router->post($fullPrefix, $controller . '@store', $middleware);
        $this->router->put($fullPrefix . '/{id}', $controller . '@update', $middleware);
        $this->router->patch($fullPrefix . '/{id}', $controller . '@update', $middleware);
        $this->router->delete($fullPrefix . '/{id}', $controller . '@destroy', $middleware);
    }

    public function setVersion($version): self
    {
        $this->version = $version;
        return $this;
    }
}