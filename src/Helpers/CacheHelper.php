<?php

namespace Yabasi\Helpers;

use Exception;
use Yabasi\Cache\CacheManager;

class CacheHelper
{
    protected static ?CacheManager $cacheManager = null;

    protected static function getCacheManager(): ?CacheManager
    {
        if (self::$cacheManager === null) {
            try {
                self::$cacheManager = app(CacheManager::class);
            } catch (Exception $e) {
                // Log the error or handle it as needed
                return null;
            }
        }
        return self::$cacheManager;
    }

    public static function get(string $key, $default = null)
    {
        $manager = self::getCacheManager();
        return $manager ? $manager->get($key, $default) : $default;
    }

    public static function set(string $key, $value, ?int $ttl = null): bool
    {
        $manager = self::getCacheManager();
        return $manager ? $manager->set($key, $value, $ttl) : false;
    }

    public static function delete(string $key): bool
    {
        $manager = self::getCacheManager();
        return $manager ? $manager->delete($key) : false;
    }

    public static function clear(): bool
    {
        $manager = self::getCacheManager();
        return $manager ? $manager->clear() : false;
    }

    public static function has(string $key): bool
    {
        $manager = self::getCacheManager();
        return $manager ? $manager->has($key) : false;
    }

    public static function many(array $keys): array
    {
        $manager = self::getCacheManager();
        return $manager ? $manager->many($keys) : [];
    }

    public static function setMany(array $values, ?int $ttl = null): bool
    {
        $manager = self::getCacheManager();
        return $manager ? $manager->setMany($values, $ttl) : false;
    }

    public static function deleteMany(array $keys): bool
    {
        $manager = self::getCacheManager();
        return $manager ? $manager->deleteMany($keys) : false;
    }

    public static function increment(string $key, $value = 1)
    {
        $manager = self::getCacheManager();
        return $manager ? $manager->increment($key, $value) : false;
    }

    public static function decrement(string $key, $value = 1)
    {
        $manager = self::getCacheManager();
        return $manager ? $manager->decrement($key, $value) : false;
    }
}