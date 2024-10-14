<?php

namespace Yabasi\Providers;

use Yabasi\Config\Config;
use Yabasi\ServiceProvider\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('config', function () {
            return new Config();
        });

        $this->container->singleton(Config::class, function ($container) {
            return $container->get('config');
        });
    }
}