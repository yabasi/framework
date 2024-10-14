<?php

namespace Yabasi\Cache\Drivers;

use Predis\Client as RedisClient;
use Yabasi\Cache\CacheInterface;
use Yabasi\Config\Config;

class RedisCache implements CacheInterface
{
    protected RedisClient $redis;

    public function __construct(?Config $config = null)
    {
        $redisConfig = $config ? $config->get('redis.default', []) : [];
        $this->redis = new RedisClient($redisConfig);
    }

    public function get(string $key, $default = null)
    {
        $value = $this->redis->get($key);
        return $value !== null ? unserialize($value) : $default;
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $value = serialize($value);
        if ($ttl) {
            return $this->redis->setex($key, $ttl, $value) === 'OK';
        }
        return $this->redis->set($key, $value) === 'OK';
    }

    public function delete(string $key): bool
    {
        return $this->redis->del($key) > 0;
    }

    public function clear(): bool
    {
        return $this->redis->flushdb() === 'OK';
    }

    public function has(string $key): bool
    {
        return $this->redis->exists($key) > 0;
    }

    public function many(array $keys): array
    {
        $values = $this->redis->mget($keys);
        return array_map(function ($value) {
            return $value !== null ? unserialize($value) : null;
        }, array_combine($keys, $values));
    }

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

    public function deleteMany(array $keys): bool
    {
        return $this->redis->del($keys) > 0;
    }

    public function increment(string $key, $value = 1)
    {
        return $this->redis->incrby($key, $value);
    }

    public function decrement(string $key, $value = 1)
    {
        return $this->redis->decrby($key, $value);
    }
}