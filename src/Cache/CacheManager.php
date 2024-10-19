<?php

namespace Yabasi\Cache;

use Exception;
use Yabasi\Cache\Drivers\FileCache;
use Yabasi\Cache\Drivers\RedisCache;
use Yabasi\Config\Config;
use Yabasi\Container\Container;

/**
 * CacheManager class for managing different cache drivers.
 *
 * This class provides a unified interface to work with different cache drivers,
 * such as file-based caching and Redis caching.
 */
class CacheManager
{
    /** @var Container */
    protected Container $container;

    /** @var array */
    protected array $stores = [];

    /** @var string */
    protected string $defaultDriver;

    /** @var Config|null */
    protected ?Config $config;

    /**
     * CacheManager constructor.
     *
     * @param Container $container The dependency injection container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $container->has('config') ? $container->get('config') : null;
        $this->defaultDriver = $this->getConfig('cache.default', 'file');
    }

    /**
     * Get a configuration value.
     *
     * @param string $key     The configuration key
     * @param mixed|null $default The default value if the key is not found
     *
     * @return mixed The configuration value
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config ? $this->config->get($key, $default) : $default;
    }

    /**
     * Get a cache store instance.
     *
     * @param string|null $driver The name of the cache driver to use
     *
     * @return CacheInterface The cache store instance
     *
     * @throws Exception If the specified driver is not supported
     */
    public function driver(?string $driver = null): CacheInterface
    {
        $driver = $driver ?: $this->defaultDriver;

        if (!isset($this->stores[$driver])) {
            $this->stores[$driver] = $this->createDriver($driver);
        }

        return $this->stores[$driver];
    }

    /**
     * Create a new cache driver instance.
     *
     * @param string $driver The name of the cache driver to create
     *
     * @return CacheInterface The created cache driver instance
     *
     * @throws Exception If the specified driver is not supported
     */
    protected function createDriver(string $driver): CacheInterface
    {
        $method = 'create' . ucfirst($driver) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new Exception("Driver [{$driver}] not supported.");
    }

    /**
     * Create a file cache driver instance.
     *
     * @return FileCache The file cache driver instance
     */
    protected function createFileDriver(): FileCache
    {
        return new FileCache($this->getConfig('cache.stores.file.path'));
    }

    /**
     * Create a Redis cache driver instance.
     *
     * @return RedisCache The Redis cache driver instance
     */
    protected function createRedisDriver(): RedisCache
    {
        return new RedisCache($this->config);
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param string $method    The method to call on the driver
     * @param array $parameters The parameters to pass to the method
     *
     * @return mixed The result of the method call
     *
     * @throws Exception If the method is not supported by the cache driver
     */
    public function __call(string $method, array $parameters)
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