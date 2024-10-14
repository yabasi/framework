<?php

namespace Yabasi\Providers;

use Yabasi\Queue\QueueManager;
use Yabasi\ServiceProvider\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(QueueManager::class, function ($container) {
            return new QueueManager(
                $container->get('config')
            );
        });
    }
}