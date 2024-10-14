<?php

namespace Yabasi\Queue;

use Yabasi\Config\Config;
use Yabasi\Logging\Logger;

abstract class Job
{
    protected $data;
    protected $attempts = 0;
    protected $maxAttempts = 3;
    protected $delay = 0;
    protected $progress = 0;
    protected $logger;

    public function __construct($data = [])
    {
        $this->data = $data;
        $config = new Config();
        $this->logger = new Logger($config, 'queue');
    }

    abstract public function handle();

    public function failed($exception)
    {
        $this->logger->error("Job failed: " . get_class($this) . ". Error: " . $exception->getMessage());
    }

    public function setAttempts($attempts)
    {
        $this->attempts = $attempts;
    }

    public function incrementAttempts()
    {
        $this->attempts++;
    }

    public function getAttempts()
    {
        return $this->attempts;
    }

    public function setMaxAttempts($maxAttempts)
    {
        $this->maxAttempts = $maxAttempts;
    }

    public function getMaxAttempts()
    {
        return $this->maxAttempts;
    }

    public function setDelay($delay)
    {
        $this->delay = $delay;
    }

    public function getDelay()
    {
        return $this->delay;
    }

    public function setProgress($progress)
    {
        $this->progress = $progress;
    }

    public function getProgress()
    {
        return $this->progress;
    }
}