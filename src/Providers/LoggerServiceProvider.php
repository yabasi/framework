<?php

namespace Yabasi\Providers;

use Yabasi\Logging\Logger;
use Yabasi\ServiceProvider\ServiceProvider;

class LoggerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Logger::class, function ($container) {
            return new Logger($container->get('config'));
        });
    }
}