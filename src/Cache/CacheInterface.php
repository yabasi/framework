<?php

namespace Yabasi\Cache;

interface CacheInterface
{
    public function get(string $key, $default = null);
    public function set(string $key, $value, ?int $ttl = null): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
    public function has(string $key): bool;
    public function many(array $keys): array;
    public function setMany(array $values, ?int $ttl = null): bool;
    public function deleteMany(array $keys): bool;
    public function increment(string $key, $value = 1);
    public function decrement(string $key, $value = 1);
}