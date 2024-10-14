<?php

namespace Yabasi\Providers;

use Yabasi\Database\Connection;
use Yabasi\Database\Migrations\Migrator;
use Yabasi\Filesystem\Filesystem;
use Yabasi\ServiceProvider\ServiceProvider;

class MigratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Migrator::class, function ($container) {
            return new Migrator(
                $container->make(Connection::class),
                $container->make(Filesystem::class),
                $container->make('config')->get('database.migrations_path')
            );
        });
    }
}