<?php

namespace Yabasi\Providers;

use Yabasi\Middleware\CsrfMiddleware;
use Yabasi\Middleware\MiddlewareManager;
use Yabasi\Security\CsrfProtection;
use Yabasi\ServiceProvider\ServiceProvider;

class CsrfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(CsrfProtection::class, function ($container) {
            return new CsrfProtection($container->get('session'));
        });

        $this->container->singleton(CsrfMiddleware::class, function ($container) {
            return new CsrfMiddleware($container->get(CsrfProtection::class));
        });
    }

    public function boot(): void
    {
        if ($this->container->has('middleware')) {
            $middlewareManager = $this->container->get('middleware');
            if ($middlewareManager instanceof MiddlewareManager) {
                $middlewareManager->addGlobalMiddleware(CsrfMiddleware::class);
            }
        }
    }
}