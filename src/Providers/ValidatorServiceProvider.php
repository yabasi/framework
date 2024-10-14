<?php

namespace Yabasi\Providers;

use Yabasi\Localization\Translator;
use Yabasi\ServiceProvider\ServiceProvider;
use Yabasi\Validation\Validator;

class ValidatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Validator::class, function ($container) {
            return new Validator($container->get(Translator::class));
        });
    }
}