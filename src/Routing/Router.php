<?php

namespace Yabasi\Routing;

use Closure;
use Exception;
use Yabasi\Container\Container;
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
            throw new Exception("Route not found", 404);
        }

        $middlewares = $this->resolveMiddleware($route['middleware'] ?? []);

        if (str_starts_with($route['uri'], $this->apiPrefix . '/' . $this->version)) {
            array_unshift($middlewares, ApiMiddleware::class);
        }

        return $this->runMiddlewareChain($request, $middlewares, function ($request) use ($route) {
            return $this->handleRoute($route, $request);
        });
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

    protected function runMiddlewareChain(Request $request, array $middlewares, callable $target): Response
    {
        return $this->middlewareManager->runMiddleware($request, $middlewares, $target);
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
            if ($routePart !== $requestParts[$index] && !preg_match('/^{.+}$/', $routePart)) {
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
            list($controller, $action) = explode('@', $handler);
            $controllerClass = "Yabasi\\Controllers\\{$controller}";

            $controllerInstance = $this->container->make($controllerClass);

            $reflectionMethod = new \ReflectionMethod($controllerInstance, $action);
            $parameters = $reflectionMethod->getParameters();

            $args = [];
            foreach ($parameters as $parameter) {
                $parameterType = $parameter->getType();
                if ($parameterType) {
                    $typeName = $parameterType->getName();
                    if ($typeName === Request::class) {
                        $args[] = $request;
                    } elseif (is_subclass_of($typeName, FormRequest::class)) {
                        $formRequest = $this->container->make($typeName, ['request' => $request]);
                        $args[] = $formRequest;
                    }
                } elseif ($parameter->getName() === 'id') {
                    $args[] = $this->getRouteParameter($route['uri'], $request->getUri(), 'id');
                }
            }

            $result = $controllerInstance->$action(...$args);

            if ($result instanceof Response) {
                return $result;
            }

            $response = new Response();
            $response->setContent($result);
            return $response;
        } elseif (is_callable($handler)) {
            return $handler($request);
        }

        throw new \Exception("Invalid route handler");
    }

    protected function getRouteParameter($routeUri, $requestUri, $paramName)
    {
        $routeParts = explode('/', trim($routeUri, '/'));
        $requestParts = explode('/', trim($requestUri, '/'));

        foreach ($routeParts as $index => $part) {
            if (preg_match('/^{(.+)}$/', $part, $matches)) {
                if ($matches[1] === $paramName) {
                    return $requestParts[$index] ?? null;
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