<?php

namespace Yabasi\Middleware;

use Yabasi\Controller\Controller;

class MiddlewareRegistrar
{
    protected Controller $controller;
    protected string|array $middleware;

    public function __construct(Controller $controller, string|array $middleware)
    {
        $this->controller = $controller;
        $this->middleware = $middleware;
    }

    /**
     * Specify methods the middleware should only apply to.
     *
     * @param array|string|null $methods
     * @return $this
     */
    public function only(array|string|null $methods): self
    {
        $this->controller->registerMiddleware($this->middleware, 'only', $methods);
        return $this;
    }

    /**
     * Specify methods the middleware should not apply to.
     *
     * @param array|string|null $methods
     * @return $this
     */
    public function except(array|string|null $methods): self
    {
        $this->controller->registerMiddleware($this->middleware, 'except', $methods);
        return $this;
    }
}