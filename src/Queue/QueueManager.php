<?php

namespace Yabasi\Queue;

use DateTime;
use InvalidArgumentException;
use Predis\Client as RedisClient;
use Yabasi\Config\Config;
use Yabasi\Logging\Logger;

class QueueManager
{
    protected Config $config;
    protected RedisClient $redis;
    protected Logger $logger;
    protected string $defaultQueue = 'default';

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->redis = new RedisClient($config->get('queue.redis', []));
        $this->logger = new Logger($config, 'queue');
    }

    public function push(Job $job, $queue = null)
    {
        $queue = $queue ?: $this->defaultQueue;
        $jobData = serialize($job);
        $this->redis->rpush("queues:{$queue}", $jobData);
        $this->logger->info("Job pushed to queue: {$queue}");
    }

    public function later($delay, Job $job, $queue = null)
    {
        $queue = $queue ?: $this->defaultQueue;
        $jobData = serialize($job);

        if ($delay instanceof DateTime) {
            $executeAt = $delay->getTimestamp();
        } elseif (is_numeric($delay)) {
            $executeAt = time() + $delay;
        } else {
            throw new InvalidArgumentException("Invalid delay format");
        }

        $this->redis->zadd("delayed:{$queue}", $executeAt, $jobData);
        $this->logger->info("Job scheduled for execution at " . date('Y-m-d H:i:s', $executeAt) . " in queue: {$queue}");
    }

    public function pop($queue = null)
    {
        $queue = $queue ?: $this->defaultQueue;
        $jobData = $this->redis->lpop("queues:{$queue}");
        if ($jobData) {
            $this->logger->info("Job popped from queue: {$queue}");
            return unserialize($jobData);
        }
        $this->logger->info("No job available in queue: {$queue}");
        return null;
    }

    public function processDelayedJobs()
    {
        foreach ($this->redis->keys('delayed:*') as $delayedQueue) {
            $queue = str_replace('delayed:', '', $delayedQueue);
            $now = time();
            $jobs = $this->redis->zrangebyscore($delayedQueue, '-inf', $now);

            foreach ($jobs as $jobData) {
                $this->redis->zrem($delayedQueue, $jobData);
                $this->redis->rpush("queues:{$queue}", $jobData);
                $this->logger->info("Delayed job moved to queue: {$queue}");
            }
        }
    }
}