<?php

namespace Yabasi\Api;

use Yabasi\Cache\CacheManager;

/**
 * RateLimiter class for managing API request limits.
 *
 * This class provides functionality to limit the number of requests
 * a client can make to the API within a specified time frame.
 */
class RateLimiter
{
    /** @var CacheManager */
    public CacheManager $cache;

    /** @var int */
    protected int $maxAttempts;

    /** @var int */
    protected int $decayMinutes;

    /**
     * RateLimiter constructor.
     *
     * @param CacheManager $cache        The cache manager instance
     * @param int $maxAttempts  Maximum number of attempts allowed
     * @param int $decayMinutes Time in minutes before the limit resets
     */
    public function __construct(CacheManager $cache, int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $this->cache = $cache;
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    /**
     * Check if the number of attempts for a given key has been exceeded.
     *
     * @param string $key          The unique key for the rate limit
     * @param int|null $maxAttempts  Maximum number of attempts allowed (optional)
     * @param int|null $decayMinutes Time in minutes before the limit resets (optional)
     *
     * @return bool True if too many attempts, false otherwise
     */
    public function tooManyAttempts(string $key, int $maxAttempts = null, int $decayMinutes = null): bool
    {
        $maxAttempts = $maxAttempts ?: $this->maxAttempts;
        $decayMinutes = $decayMinutes ?: $this->decayMinutes;

        if ($this->attempts($key) >= $maxAttempts) {
            if ($this->cache->has($key . ':timer')) {
                return true;
            }

            $this->resetAttempts($key);
        }

        return false;
    }

    /**
     * Increment the number of attempts for a given key.
     *
     * @param string $key          The unique key for the rate limit
     * @param int|null $decayMinutes Time in minutes before the limit resets (optional)
     *
     * @return int The new number of attempts
     */
    public function hit(string $key, int $decayMinutes = null): int
    {
        $decayMinutes = $decayMinutes ?: $this->decayMinutes;

        $this->cache->increment($key);
        $this->cache->set($key . ':timer', $this->availableAt($decayMinutes), $decayMinutes * 60);

        return $this->attempts($key);
    }

    /**
     * Get the number of attempts for a given key.
     *
     * @param string $key The unique key for the rate limit
     *
     * @return int The number of attempts
     */
    public function attempts(string $key): int
    {
        return $this->cache->get($key, 0);
    }

    /**
     * Reset the number of attempts for a given key.
     *
     * @param string $key The unique key for the rate limit
     *
     * @return bool True if the reset was successful, false otherwise
     */
    public function resetAttempts(string $key): bool
    {
        return $this->cache->forget($key);
    }

    /**
     * Get the timestamp when the rate limit will be available again.
     *
     * @param int $delay The delay in minutes
     *
     * @return float|int The timestamp when the rate limit will be available
     */
    protected function availableAt(int $delay = 0): float|int
    {
        return time() + ($delay * 60);
    }
}