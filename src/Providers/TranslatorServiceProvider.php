<?php

namespace Yabasi\Providers;

use Yabasi\Localization\Translator;
use Yabasi\ServiceProvider\ServiceProvider;

class TranslatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Translator::class, function ($container) {
            return new Translator($container->get('config'));
        });
    }
}