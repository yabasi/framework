<?php

namespace Yabasi\Providers;

use Yabasi\Asset\AssetManager;
use Yabasi\ServiceProvider\ServiceProvider;

class AssetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(AssetManager::class, function ($container) {
            return new AssetManager($container->get('config'));
        });
    }

    public function boot(): void
    {
        
    }
}