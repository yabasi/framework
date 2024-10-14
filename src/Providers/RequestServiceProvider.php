<?php

namespace Yabasi\Providers;

use Yabasi\Http\Request;
use Yabasi\ServiceProvider\ServiceProvider;

class RequestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Request::class, function () {
            return new Request();
        });
    }
}