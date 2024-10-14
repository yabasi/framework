<?php

namespace Yabasi;

use Yabasi\Container\Container;
use Yabasi\Http\Request;
use Yabasi\Http\Response;
use Yabasi\Logging\CustomExceptionHandler;
use Yabasi\Routing\Router;

class App
{
    protected $container;
    protected $router;
    private CustomExceptionHandler $exceptionHandler;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->router = new Router($container);
        $this->registerRoutes();

        $this->exceptionHandler = new CustomExceptionHandler(
            $this->container->get('config')->get('app.debug', false)
        );
    }

    public function run(): void
    {
        $request = new Request();
        try {
            $response = $this->router->dispatch($request);
            if ($response instanceof Response) {
                $response->send();
            } else {
                echo $response;
            }
        } catch (\Exception $e) {
            $this->exceptionHandler->handle($e);
        }
    }

    protected function registerRoutes(): void
    {
        $routes = require BASE_PATH . '/routes/web.php';
        $routes($this->router);
    }
}