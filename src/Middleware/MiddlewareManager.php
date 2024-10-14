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
        $middlewareConfig = $this->config->get('middleware', []);

        $this->globalMiddleware = $middlewareConfig['global'] ?? [];
        $this->middlewareAliases = $middlewareConfig['route'] ?? [];
        $this->middlewareGroups = $middlewareConfig['groups'] ?? [];
    }

    public function resolveMiddleware($middleware): array
    {
        if (is_string($middleware)) {
            return $this->resolveMiddlewareAlias($middleware);
        }

        return [$middleware];
    }

    protected function resolveMiddlewareAlias(string $name): array
    {
        if (isset($this->middlewareAliases[$name])) {
            return [$this->middlewareAliases[$name]];
        }

        if (isset($this->middlewareGroups[$name])) {
            return $this->middlewareGroups[$name];
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

        $result = $pipeline($request);

        if (!$result instanceof Response) {
            $response = new Response();
            $response->setContent($result);
            return $response;
        }

        return $result;
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