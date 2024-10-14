<?php

namespace Yabasi\Providers;

use Yabasi\Http\Response;
use Yabasi\ServiceProvider\ServiceProvider;

class ResponseServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->container->singleton(Response::class, function () {
            return new Response();
        });
    }
}