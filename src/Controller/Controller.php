<?php

namespace Yabasi\Controller;

use Exception;
use Yabasi\Container\Container;
use Yabasi\Http\Response;
use Yabasi\Middleware\MiddlewareRegistrar;
use Yabasi\View\Template;
use Yabasi\Middleware\MiddlewareManager;

abstract class Controller
{
    protected Container $container;
    protected Template $template;
    protected array $middleware = [];
    protected array $middlewareGroups = [];
    protected array $middlewareExcept = [];
    protected array $middlewareOnly = [];
    protected ?MiddlewareManager $middlewareManager;

    /**
     * @throws Exception
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->template = $container->get(Template::class);
        $this->middlewareManager = $container->get(MiddlewareManager::class);
    }

    /**
     * Register middleware on the controller.
     *
     * @param string|array $middleware
     * @return MiddlewareRegistrar
     */
    protected function middleware(string|array $middleware): MiddlewareRegistrar
    {
        return new MiddlewareRegistrar($this, $middleware);
    }

    /**
     * Register the middleware.
     *
     * @param string|array $middleware
     * @param string $type
     * @param array|string|null $methods
     * @return void
     */
    public function registerMiddleware(string|array $middleware, string $type, array|string|null $methods): void
    {
        $methods = is_array($methods) ? $methods : [$methods];

        if ($type === 'only') {
            $this->middlewareOnly[$middleware] = $methods;
        } elseif ($type === 'except') {
            $this->middlewareExcept[$middleware] = $methods;
        }

        if (!in_array($middleware, $this->middleware)) {
            $this->middleware[] = $middleware;
        }
    }

    /**
     * Get the middleware assigned to the controller.
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Get the middleware that should be run for the given method.
     *
     * @param string $method
     * @return array
     */
    public function getMiddlewareForMethod(string $method): array
    {
        $middleware = [];

        foreach ($this->middleware as $name) {
            // Check if middleware should only run on specific methods
            if (isset($this->middlewareOnly[$name])) {
                if (!in_array($method, $this->middlewareOnly[$name])) {
                    continue;
                }
            }

            // Check if middleware should be excluded from specific methods
            if (isset($this->middlewareExcept[$name])) {
                if (in_array($method, $this->middlewareExcept[$name])) {
                    continue;
                }
            }

            $middleware[] = $name;
        }

        return $middleware;
    }

    /**
     * @param string $view
     * @param array $data
     * @return Response
     */
    protected function view(string $view, array $data = []): Response
    {
        $content = $this->template->render($view, $data);
        $response = new Response();
        $response->setContent($content);
        return $response;
    }

    protected function json(array $data, int $status = 200): Response
    {
        $response = new Response();
        $response->setContent(json_encode($data));
        $response->setHeader('Content-Type', 'application/json');
        $response->setStatusCode($status);
        return $response;
    }
}