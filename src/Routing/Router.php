<?php

namespace Yabasi\Routing;

use Closure;
use Exception;
use ReflectionMethod;
use Yabasi\Container\Container;
use Yabasi\Controller\Controller;
use Yabasi\Exceptions\RouteNotFoundException;
use Yabasi\Http\FormRequest;
use Yabasi\Http\Request;
use Yabasi\Http\Response;
use Yabasi\Logging\Logger;
use Yabasi\Middleware\MiddlewareManager;
use Yabasi\Middleware\ApiMiddleware;

class Router
{
    protected Container $container;
    protected MiddlewareManager $middlewareManager;
    protected array $routes = [];
    protected array $groupStack = [];
    protected $logger;
    protected string $apiPrefix = 'api';
    protected string $version = 'v1';

    /**
     * @throws Exception
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->middlewareManager = $container->get(MiddlewareManager::class);
        $this->logger = $container->get(Logger::class);
    }

    public function get($uri, $handler, $middleware = []): void
    {
        $this->addRoute('GET', $uri, $handler, $middleware);
    }

    public function post($uri, $handler, $middleware = []): void
    {
        $this->addRoute('POST', $uri, $handler, $middleware);
    }

    public function put($uri, $action, $middleware = [])
    {
        $this->addRoute('PUT', $uri, $action, $middleware);
    }

    public function patch($uri, $action, $middleware = [])
    {
        $this->addRoute('PATCH', $uri, $action, $middleware);
    }

    public function delete($uri, $action, $middleware = [])
    {
        $this->addRoute('DELETE', $uri, $action, $middleware);
    }

    public function options($uri, $action, $middleware = [])
    {
        $this->addRoute('OPTIONS', $uri, $action, $middleware);
    }

    public function addRoute($method, $uri, $handler, $middleware = []): void
    {
        $groupMiddleware = $this->getGroupMiddleware();
        $groupPrefix = $this->getGroupPrefix();

        $uri = trim($groupPrefix . '/' . trim($uri, '/'), '/');
        $uri = $uri === '' ? '/' : '/' . $uri;

        $this->routes[] = [
            'method'     => $method,
            'uri'        => $uri,
            'handler'    => $handler,
            'middleware' => array_merge($groupMiddleware, $middleware)
        ];
    }

    public function group(array $attributes, Closure $callback): void
    {
        $this->groupStack[] = $attributes;

        call_user_func($callback, $this);

        array_pop($this->groupStack);
    }

    protected function getGroupMiddleware(): array
    {
        return collect($this->groupStack)->pluck('middleware', null)->flatten()->all();
    }

    protected function getGroupPrefix(): string
    {
        return collect($this->groupStack)->pluck('prefix', null)->implode('/');
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * @throws Exception
     */
    public function dispatch(Request $request): Response
    {
        $route = $this->findRoute($request);

        if (!$route) {
            throw new RouteNotFoundException("No route found for " . $request->getUri());
        }

        // Collect all middleware
        $middlewares = $this->resolveRouteMiddleware($route, $request);

        if (str_starts_with($route['uri'], $this->apiPrefix . '/' . $this->version)) {
            array_unshift($middlewares, 'api');
        }

        return $this->runMiddlewareChain($request, $middlewares, function ($request) use ($route) {
            return $this->handleRoute($route, $request);
        });
    }

    /**
     * Resolve all middleware for the route including controller middleware
     */
    protected function resolveRouteMiddleware(array $route, Request $request): array
    {
        $middlewares = [];

        if (!empty($route['middleware'])) {
            foreach ((array)$route['middleware'] as $middleware) {
                $middlewares = array_merge(
                    $middlewares,
                    $this->middlewareManager->resolveMiddleware($middleware)
                );
            }
        }

        if (is_string($route['handler']) && str_contains($route['handler'], '@')) {
            [$controller, $method] = explode('@', $route['handler']);
            $controllerClass = "Yabasi\\Controllers\\{$controller}";

            if (class_exists($controllerClass)) {
                $controllerInstance = $this->container->make($controllerClass);
                if ($controllerInstance instanceof Controller) {
                    $controllerMiddlewares = $controllerInstance->getMiddlewareForMethod($method);
                    foreach ($controllerMiddlewares as $middleware) {
                        $middlewares = array_merge(
                            $middlewares,
                            $this->middlewareManager->resolveMiddleware($middleware)
                        );
                    }
                }
            }
        }

        return array_unique($middlewares);
    }

    public function resource($name, $controller, $middleware = []): void
    {
        $this->get($name, $controller . '@index', $middleware);
        $this->get($name . '/{id}', $controller . '@show', $middleware);
        $this->post($name, $controller . '@store', $middleware);
        $this->put($name . '/{id}', $controller . '@update', $middleware);
        $this->patch($name . '/{id}', $controller . '@update', $middleware);
        $this->delete($name . '/{id}', $controller . '@destroy', $middleware);
    }

    protected function resolveMiddleware(array $middlewareNames): array
    {
        $resolvedMiddleware = [];
        foreach ($middlewareNames as $name) {
            $resolved = $this->middlewareManager->resolveMiddleware($name);
            $resolvedMiddleware = array_merge($resolvedMiddleware, $resolved);
        }
        return $resolvedMiddleware;
    }

    protected function runMiddlewareChain(Request $request, array $middlewares, callable $destination): Response
    {
        return $this->middlewareManager->runMiddleware($request, $middlewares, $destination);
    }

    protected function findRoute(Request $request): ?array
    {
        $method = $request->getMethod();
        $uri = $this->getUriWithoutQueryString($request->getUri());

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->uriMatches($route['uri'], $uri)) {
                return $route;
            }
        }

        return null;
    }

    protected function getUriWithoutQueryString($uri): string
    {
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        return '/' . trim($uri, '/');
    }

    protected function uriMatches($routeUri, $requestUri): bool
    {
        $routeParts = explode('/', trim($routeUri, '/'));
        $requestParts = explode('/', trim($requestUri, '/'));

        if (count($routeParts) !== count($requestParts)) {
            return false;
        }

        foreach ($routeParts as $index => $routePart) {
            if (preg_match('/^{.+}$/', $routePart)) {
                continue;
            }
            if ($routePart !== $requestParts[$index]) {
                return false;
            }
        }

        return true;
    }


    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    protected function handleRoute($route, Request $request)
    {
        $handler = $route['handler'];

        if (is_string($handler)) {
            [$controller, $action] = explode('@', $handler);
            $controllerClass = "Yabasi\\Controllers\\{$controller}";

            $controllerInstance = $this->container->make($controllerClass);

            $parameters = $this->getRouteParameters($route['uri'], $request->getUri());

            $reflectionMethod = new \ReflectionMethod($controllerInstance, $action);
            $methodParams = $reflectionMethod->getParameters();

            $args = [];
            foreach ($methodParams as $param) {
                if ($param->getType() && $param->getType()->getName() === Request::class) {
                    $args[] = $request;
                } elseif (isset($parameters[$param->getName()])) {
                    $args[] = $parameters[$param->getName()];
                } else {
                    $args[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
                }
            }

            $result = $controllerInstance->$action(...$args);

            if ($result instanceof Response) {
                return $result;
            }

            $response = new Response();
            $response->setContent($result);
            return $response;
        }

        if ($handler instanceof Closure) {
            $result = $handler($request);

            if ($result instanceof Response) {
                return $result;
            }

            $response = new Response();
            $response->setContent($result);
            return $response;
        }

        throw new Exception("Invalid route handler");
    }

    protected function getRouteParameters(string $routeUri, string $requestUri): array
    {
        $routeParts = explode('/', trim($routeUri, '/'));
        $requestParts = explode('/', trim($requestUri, '/'));
        $parameters = [];

        foreach ($routeParts as $index => $part) {
            if (preg_match('/^{(.+)}$/', $part, $matches)) {
                $paramName = $matches[1];
                $parameters[$paramName] = $requestParts[$index] ?? null;
            }
        }

        return $parameters;
    }

    protected function getRouteParameter(string $routeUri, string $requestUri, string $paramName): ?string
    {
        $routeParts = explode('/', trim($routeUri, '/'));
        $requestParts = explode('/', trim($requestUri, '/'));

        foreach ($routeParts as $index => $part) {
            if (preg_match('/^{(.+)}$/', $part, $matches)) {
                $currentParamName = $matches[1];
                if ($currentParamName === $paramName && isset($requestParts[$index])) {
                    return $requestParts[$index];
                }
            }
        }

        return null;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}