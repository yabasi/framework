<?php

namespace Yabasi\Cache;

use Exception;
use Yabasi\Cache\Drivers\FileCache;
use Yabasi\Cache\Drivers\RedisCache;
use Yabasi\Config\Config;
use Yabasi\Container\Container;

class CacheManager
{
    protected Container $container;
    protected array $stores = [];
    protected string $defaultDriver;
    protected ?Config $config;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $container->has('config') ? $container->get('config') : null;
        $this->defaultDriver = $this->getConfig('cache.default', 'file');
    }

    protected function getConfig($key, $default = null)
    {
        return $this->config ? $this->config->get($key, $default) : $default;
    }

    public function driver(?string $driver = null)
    {
        $driver = $driver ?: $this->defaultDriver;

        if (!isset($this->stores[$driver])) {
            $this->stores[$driver] = $this->createDriver($driver);
        }

        return $this->stores[$driver];
    }

    protected function createDriver(string $driver)
    {
        $method = 'create' . ucfirst($driver) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new Exception("Driver [{$driver}] not supported.");
    }

    protected function createFileDriver()
    {
        return new FileCache($this->getConfig('cache.stores.file.path'));
    }

    protected function createRedisDriver()
    {
        return new RedisCache($this->config);
    }

    public function __call($method, $parameters)
    {
        $driver = $this->driver();

        if (method_exists($driver, $method)) {
            return $driver->$method(...$parameters);
        }

        $alternativeMethods = [
            'put' => 'set',
            'add' => 'set',
            'forever' => 'set',
            'remember' => 'get',
            'rememberForever' => 'get',
        ];

        if (isset($alternativeMethods[$method]) && method_exists($driver, $alternativeMethods[$method])) {
            return $driver->{$alternativeMethods[$method]}(...$parameters);
        }

        throw new Exception("Method [$method] not supported by cache driver.");
    }
}