<?php

namespace Yabasi;

use Yabasi\Container\Container;
use Yabasi\Exceptions\RouteNotFoundException;
use Yabasi\Http\Request;
use Yabasi\Http\Response;
use Yabasi\Logging\CustomExceptionHandler;
use Yabasi\Routing\Router;
use Yabasi\View\Template;

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
        } catch (RouteNotFoundException $e) {
            $this->handle404($request);
        } catch (\Exception $e) {
            $this->exceptionHandler->handle($e);
        }
    }

    protected function registerRoutes(): void
    {
        $routes = require BASE_PATH . '/routes/web.php';
        $routes($this->router);
    }

    protected function handle404(Request $request): void
    {
        try {
            $template = $this->container->get(Template::class);
            $content = $template->render('404');
            $response = new Response($content, 404);
        } catch (\Exception $e) {
            $debugInfo = '';
            if ($this->container->get('config')->get('app.debug', false)) {
                $debugInfo = "<h2>Debug Info:</h2>";
                $debugInfo .= "<p>Error: " . $e->getMessage() . "</p>";
                $debugInfo .= "<p>File: " . $e->getFile() . "</p>";
                $debugInfo .= "<p>Line: " . $e->getLine() . "</p>";
                $debugInfo .= "<p>Trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
            }
            $response = new Response('404 - Page Not Found' . $debugInfo, 404);
        }
        $response->send();
    }
}