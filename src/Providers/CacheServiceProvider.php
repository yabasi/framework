<?php

namespace Yabasi\Providers;

use Yabasi\Cache\CacheManager;
use Yabasi\ServiceProvider\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(CacheManager::class, function ($container) {
            return new CacheManager($container);
        });

        $this->container->singleton('cache', function ($container) {
            return $container->get(CacheManager::class);
        });
    }
}