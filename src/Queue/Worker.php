<?php

namespace Yabasi\Queue;

use Exception;
use Yabasi\Config\Config;
use Yabasi\Logging\Logger;

class Worker
{
    protected $queueManager;
    protected $logger;

    public function __construct(QueueManager $queueManager)
    {
        $this->queueManager = $queueManager;
        $config = new Config();
        $this->logger = new Logger($config, 'queue');
    }

    public function processNextJob($queue = null)
    {
        $this->queueManager->processDelayedJobs();

        $job = $this->queueManager->pop($queue);

        if ($job) {
            $this->process($job);
        } else {
            $this->logger->info("No job available to process.");
        }
    }

    protected function process(Job $job)
    {
        try {
            $this->logger->info("Processing job: " . get_class($job));
            $job->incrementAttempts();
            $job->handle();
            $this->logger->info("Job processed successfully: " . get_class($job));
        } catch (Exception $e) {
            $this->logger->error("Job failed: " . get_class($job) . ". Error: " . $e->getMessage());
            if ($job->getAttempts() < $job->getMaxAttempts()) {
                $this->queueManager->later(
                    $job->getDelay() * $job->getAttempts(),
                    $job
                );
                $this->logger->info("Job rescheduled: " . get_class($job));
            } else {
                $job->failed($e);
                $this->logger->error("Job failed and will not be retried: " . get_class($job));
            }
        }
    }
}