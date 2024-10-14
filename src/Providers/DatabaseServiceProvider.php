<?php

namespace Yabasi\Providers;

use Yabasi\Config\Config;
use Yabasi\Database\Connection;
use Yabasi\Logging\Logger;
use Yabasi\ServiceProvider\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Connection::class, function($c) {
            $config = $c->get(Config::class);
            $logger = $c->get(Logger::class);
            return new Connection($config, $logger);
        });

        $this->container->singleton('database', function($c) {
            return $c->get(Connection::class);
        });
    }
}