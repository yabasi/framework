<?php

namespace Yabasi\Api;

use Yabasi\Cache\CacheManager;

class RateLimiter
{
    public $cache;
    protected $maxAttempts;
    protected $decayMinutes;

    public function __construct(CacheManager $cache, $maxAttempts = 60, $decayMinutes = 1)
    {
        $this->cache = $cache;
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    public function tooManyAttempts($key, $maxAttempts = null, $decayMinutes = null)
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

    public function hit($key, $decayMinutes = null)
    {
        $decayMinutes = $decayMinutes ?: $this->decayMinutes;

        $this->cache->increment($key);
        $this->cache->set($key . ':timer', $this->availableAt($decayMinutes), $decayMinutes * 60);

        return $this->attempts($key);
    }

    public function attempts($key)
    {
        return $this->cache->get($key, 0);
    }

    public function resetAttempts($key)
    {
        return $this->cache->forget($key);
    }

    protected function availableAt($delay = 0)
    {
        return time() + ($delay * 60);
    }
}