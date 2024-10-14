<?php

namespace Yabasi\Providers;

use Yabasi\Database\Connection;
use Yabasi\Localization\Translator;
use Yabasi\ServiceProvider\ServiceProvider;
use Yabasi\Validation\Validator;

class ValidationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Validator::class, function ($container) {
            return new Validator(
                $container->get(Translator::class),
                $container->get(Connection::class)
            );
        });
    }
}