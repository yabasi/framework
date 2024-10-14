<?php

namespace Yabasi\Providers;

use Yabasi\Middleware\CorsMiddleware;
use Yabasi\Middleware\MiddlewareManager;
use Yabasi\ServiceProvider\ServiceProvider;

class MiddlewareServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('middleware', function ($container) {
            return new MiddlewareManager($container, $container->get('config'));
        });

        $this->container->singleton(CorsMiddleware::class, function ($container) {
            return new CorsMiddleware($container->get('config'));
        });
    }

    public function boot(): void
    {
        $middlewareManager = $this->container->get('middleware');

        // CORS middleware'ini global middleware olarak ekle
        $middlewareManager->addGlobalMiddleware(CorsMiddleware::class);
    }
}