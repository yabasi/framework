<?php

namespace Yabasi\Providers;

use Yabasi\Events\EventDispatcher;
use Yabasi\ServiceProvider\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(EventDispatcher::class, function () {
            return new EventDispatcher();
        });

        $this->container->singleton('events', function ($container) {
            return $container->get(EventDispatcher::class);
        });
    }

    public function boot(): void
    {
        // Event listener'ları burada kaydedebilirsiniz
    }
}