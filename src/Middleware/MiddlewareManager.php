<?php

namespace Yabasi\Middleware;

use Closure;
use Yabasi\Config\Config;
use Yabasi\Container\Container;
use Yabasi\Http\Request;
use Yabasi\Http\Response;

class MiddlewareManager
{
    protected Container $container;
    protected Config $config;
    protected array $middlewareAliases = [];
    protected array $middlewareGroups = [];
    protected array $globalMiddleware = [];

    public function __construct(Container $container, Config $config)
    {
        $this->container = $container;
        $this->config = $config;
        $this->loadMiddlewareConfig();
    }

    protected function loadMiddlewareConfig(): void
    {
        $middlewareConfig = require BASE_PATH . '/config/middleware.php';

        $this->globalMiddleware = $middlewareConfig['global'] ?? [];
        $this->middlewareAliases = $middlewareConfig['route'] ?? [];
        $this->middlewareGroups = $middlewareConfig['groups'] ?? [];
    }

    public function resolveMiddleware($middleware): array
    {
        if (is_string($middleware)) {
            return $this->resolveMiddlewareClass($middleware);
        }

        if (is_array($middleware)) {
            $resolved = [];
            foreach ($middleware as $m) {
                $resolved = array_merge($resolved, $this->resolveMiddleware($m));
            }
            return $resolved;
        }

        return [$middleware];
    }

    protected function resolveMiddlewareClass(string $name): array
    {
        if (isset($this->middlewareAliases[$name])) {
            return [$this->middlewareAliases[$name]];
        }

        if (isset($this->middlewareGroups[$name])) {
            $resolved = [];
            foreach ($this->middlewareGroups[$name] as $groupMiddleware) {
                $resolved = array_merge($resolved, $this->resolveMiddleware($groupMiddleware));
            }
            return $resolved;
        }

        return [$name];
    }

    public function addGlobalMiddleware($middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    public function runMiddleware(Request $request, array $middlewares, Closure $target): Response
    {
        $middlewares = array_merge($this->globalMiddleware, $middlewares);

        $pipeline = array_reduce(
            array_reverse($middlewares),
            $this->carry(),
            $target
        );

        return $pipeline($request);
    }

    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_string($pipe)) {
                    $pipe = $this->container->make($pipe);
                }

                if ($pipe instanceof MiddlewareInterface) {
                    return $pipe->handle($passable, $stack);
                }

                return $stack($passable);
            };
        };
    }
}