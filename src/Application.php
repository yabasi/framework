<?php

namespace Yabasi;

use Exception;
use Yabasi\CLI\Console;
use Yabasi\Config\Config;
use Yabasi\Container\Container;
use Yabasi\Database\Connection;
use Yabasi\Events\EventDispatcher;
use Yabasi\Filesystem\Filesystem;
use Yabasi\Http\Request;
use Yabasi\Http\Response;
use Yabasi\Logging\Logger;
use Yabasi\Middleware\MiddlewareManager;
use Yabasi\Routing\Router;
use Yabasi\ServiceProvider\ServiceProvider;

class Application
{
    protected Container $container;
    private Console $console;
    protected array $serviceProviders = [];
    protected bool $booted = false;
    protected static ?self $instance = null;

    public function __construct()
    {
        $this->container = new Container();
        $this->registerBaseBindings();
        $this->registerBaseServices();
        $this->registerConfiguredProviders();
        $this->console = new Console($this->container);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    public function make($abstract)
    {
        return $this->container->make($abstract);
    }


    protected function registerBaseBindings(): void
    {
        $this->container->singleton(Application::class, $this);
        $this->container->singleton(Container::class, $this->container);
    }

    protected function registerBaseServices(): void
    {
        $this->container->singleton(Config::class, function () {
            return new Config();
        });

        $this->container->singleton(Logger::class, function ($container) {
            $config = $container->get(Config::class);
            return new Logger($config);
        });

        $this->container->singleton(Connection::class, function ($container) {
            return new Connection(
                $container->get(Config::class),
                $container->get(Logger::class)
            );
        });

        $this->container->singleton(Router::class, function ($container) {
            return new Router($container);
        });

        $this->container->singleton(MiddlewareManager::class, function ($container) {
            return new MiddlewareManager($container, $container->get(Config::class));
        });

        $this->container->singleton(EventDispatcher::class, function () {
            return new EventDispatcher();
        });

        $this->container->singleton(Filesystem::class, function () {
            return new Filesystem();
        });

    }

    protected function registerConfiguredProviders(): void
    {
        $providers = $this->container->get(Config::class)->get('providers', []);

        foreach ($providers as $providerClass) {
            $this->registerServiceProvider(new $providerClass($this->container));
        }
    }

    public function registerServiceProvider(ServiceProvider $provider): void
    {
        if (!in_array($provider, $this->serviceProviders, true)) {
            $provider->register();
            $this->serviceProviders[] = $provider;

            if ($this->booted) {
                $this->bootProvider($provider);
            }
        }
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        array_walk($this->serviceProviders, function ($provider) {
            $this->bootProvider($provider);
        });

        $this->booted = true;
    }

    protected function bootProvider(ServiceProvider $provider): void
    {
        if (method_exists($provider, 'boot')) {
            $this->container->call([$provider, 'boot']);
        }
    }

    public function run(): void
    {
        try {
            $this->boot();

            if (php_sapi_name() === 'cli') {
                $this->runConsole();
            } else {
                $this->runHttp();
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }


    protected function runConsole(): void
    {
        global $argv;
        $this->console->run($argv);
    }

    protected function runHttp(): void
    {
        $request = $this->container->make(Request::class);
        $router = $this->container->get(Router::class);

        //$this->container->get(EventDispatcher::class)->dispatch(new Event('application.starting', ['request' => $request]));

        $response = $router->dispatch($request);

        if (!$response instanceof Response) {
            $response = new Response($response);
        }

        $response->send();
    }

    protected function handleException(Exception $e): void
    {
        // Basit bir hata işleme mekanizması
        $config = $this->container->get(Config::class);
        if ($config->get('app.debug', false)) {
            echo "<h1>Error</h1>";
            echo "<p>Message: " . $e->getMessage() . "</p>";
            echo "<p>File: " . $e->getFile() . "</p>";
            echo "<p>Line: " . $e->getLine() . "</p>";
            echo "<h2>Stack Trace:</h2>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        } else {
            echo "An error occurred. Please try again later.";
        }
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}