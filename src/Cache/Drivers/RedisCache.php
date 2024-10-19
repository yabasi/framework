<?php

namespace Yabasi\Cache\Drivers;

use Predis\Client as RedisClient;
use Yabasi\Cache\CacheInterface;
use Yabasi\Config\Config;

/**
 * RedisCache class implements Redis-based caching mechanism.
 *
 * This class provides a Redis-based implementation of the CacheInterface,
 * using the Predis library to interact with a Redis server for caching operations.
 */
class RedisCache implements CacheInterface
{
    /** @var RedisClient The Redis client instance */
    protected RedisClient $redis;

    /**
     * RedisCache constructor.
     *
     * @param Config|null $config Configuration object, used to set Redis connection details
     */
    public function __construct(?Config $config = null)
    {
        $redisConfig = $config ? $config->get('redis.default', []) : [];
        $this->redis = new RedisClient($redisConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, $default = null)
    {
        $value = $this->redis->get($key);
        return $value !== null ? unserialize($value) : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $value = serialize($value);
        if ($ttl) {
            return $this->redis->setex($key, $ttl, $value) === 'OK';
        }
        return $this->redis->set($key, $value) === 'OK';
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        return $this->redis->del($key) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return $this->redis->flushdb() === 'OK';
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->redis->exists($key) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function many(array $keys): array
    {
        $values = $this->redis->mget($keys);
        return array_map(function ($value) {
            return $value !== null ? unserialize($value) : null;
        }, array_combine($keys, $values));
    }

    /**
     * {@inheritdoc}
     */
    public function setMany(array $values, ?int $ttl = null): bool
    {
        $serialized = [];
        foreach ($values as $key => $value) {
            $serialized[$key] = serialize($value);
        }

        if ($ttl) {
            $pipeline = $this->redis->pipeline();
            foreach ($serialized as $key => $value) {
                $pipeline->setex($key, $ttl, $value);
            }
            $results = $pipeline->execute();
            return !in_array(false, $results, true);
        }

        return $this->redis->mset($serialized) === 'OK';
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMany(array $keys): bool
    {
        return $this->redis->del($keys) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, $value = 1): bool|int
    {
        return $this->redis->incrby($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(string $key, $value = 1): bool|int
    {
        return $this->redis->decrby($key, $value);
    }
}