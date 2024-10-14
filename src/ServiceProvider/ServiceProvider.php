<?php

namespace Yabasi\ServiceProvider;

use Yabasi\Container\Container;

abstract class ServiceProvider
{
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    abstract public function register(): void;

    public function boot(): void
    {
        // Opsiyonel: Servisin başlatılması için gerekli işlemler
    }
}