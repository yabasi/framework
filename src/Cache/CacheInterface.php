<?php

namespace Yabasi\Cache;

/**
 * CacheInterface defines a standard interface for cache operations.
 *
 * This interface provides methods for basic cache operations such as
 * getting, setting, deleting, and checking the existence of cache items.
 */
interface CacheInterface
{
    /**
     * Fetch a value from the cache.
     *
     * @param string $key     The unique key of the item in the cache
     * @param mixed|null $default Default value to return if the key does not exist
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss
     */
    public function get(string $key, mixed $default = null);

    /**
     * Store a value in the cache.
     *
     * @param string   $key   The key under which to store the value
     * @param mixed    $value The value to store
     * @param int|null $ttl   The TTL value of this item in seconds. Null for endless
     *
     * @return bool True on success and false on failure
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Delete an item from the cache.
     *
     * @param string $key The key to remove from cache
     *
     * @return bool True if the item was successfully removed, false otherwise
     */
    public function delete(string $key): bool;

    /**
     * Clear the entire cache.
     *
     * @return bool True on success and false on failure
     */
    public function clear(): bool;

    /**
     * Determine if an item exists in the cache.
     *
     * @param string $key The cache item key
     *
     * @return bool True if the key exists, false otherwise
     */
    public function has(string $key): bool;

    /**
     * Retrieve multiple items from the cache.
     *
     * @param array $keys A list of keys that can be obtained in a single operation
     *
     * @return array An array of key => value pairs for the given keys
     */
    public function many(array $keys): array;

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param array    $values An array of key => value pairs to store in cache
     * @param int|null $ttl    The TTL value of this item in seconds. Null for endless
     *
     * @return bool True on success and false on failure
     */
    public function setMany(array $values, ?int $ttl = null): bool;

    /**
     * Delete multiple items from the cache.
     *
     * @param array $keys A list of keys to remove from cache
     *
     * @return bool True if the items were successfully removed, false otherwise
     */
    public function deleteMany(array $keys): bool;

    /**
     * Increment the value of a cache item.
     *
     * @param string $key   The key to increment
     * @param int $value The amount by which to increment
     *
     * @return int|bool The new value on success or false on failure
     */
    public function increment(string $key, int $value = 1): bool|int;

    /**
     * Decrement the value of a cache item.
     *
     * @param string $key   The key to decrement
     * @param int $value The amount by which to decrement
     *
     * @return int|bool The new value on success or false on failure
     */
    public function decrement(string $key, int $value = 1): bool|int;
}