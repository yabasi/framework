<?php

namespace Yabasi\Providers;

use Yabasi\ServiceProvider\ServiceProvider;
use Yabasi\Session\SecurityHandler;
use Yabasi\Session\SessionManager;

class SessionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(SecurityHandler::class, function () {
            return new SecurityHandler();
        });

        $this->container->singleton(SessionManager::class, function ($container) {
            return new SessionManager(
                $container->get('config'),
                $container->get(SecurityHandler::class)
            );
        });

        $this->container->singleton('session', function ($container) {
            return $container->get(SessionManager::class);
        });
    }

    public function boot(): void
    {
        // Oturum başlatma işlemi burada gerçekleştirilebilir
        $this->container->get(SessionManager::class);
    }
}