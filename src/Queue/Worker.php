<?php

declare(strict_types=1);

namespace Witals\Framework\Queue;

use Witals\Framework\Queue\Contracts\JobInterface;
use Witals\Framework\Queue\Contracts\JobMiddlewareInterface;
use Witals\Framework\Queue\Contracts\QueueWorkerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class Worker implements QueueWorkerInterface
{
    protected bool $shouldQuit = false;

    protected int $memoryLimit = 128;

    protected ?LoggerInterface $logger = null;

    public function __construct(
        protected QueueManager $manager,
    ) {
    }

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function daemon(string $connection, string $queue, array $options = []): void
    {
        $sleep = $options['sleep'] ?? 3;
        $maxTries = $options['max_tries'] ?? 0;
        $maxExceptions = $options['max_exceptions'] ?? 0;
        $backoff = $options['backoff'] ?? 0;
        $timeout = $options['timeout'] ?? 60;
        $memory = $options['memory'] ?? 128;
        $rest = $options['rest'] ?? 0;

        $this->memoryLimit = $memory;

        while (!$this->shouldQuit) {
            try {
                $job = $this->manager->connection($connection)->pop($queue);

                if ($job !== null) {
                    $this->runJob($job, $connection, $options);
                } else {
                    $this->sleep($sleep);
                }
            } catch (Throwable $e) {
                $this->logError("Queue worker error: {$e->getMessage()}");

                if ($this->memoryExceeded($memory)) {
                    $this->stop();
                }

                $this->sleep($sleep);
            }

            if ($this->memoryExceeded($memory)) {
                $this->stop();
            }
        }
    }

    public function runJob(JobInterface $job, string $connection, array $options = []): void
    {
        $maxTries = $options['max_tries'] ?? 0;
        $maxExceptions = $options['max_exceptions'] ?? 0;

        try {
            $this->logInfo("Processing job: {$job->displayName()} [{$job->jobId()}]");

            $this->runJobWithMiddleware($job);

            if (!$job->isDeleted() && !$job->isReleased()) {
                $job->delete();
            }

            $this->logInfo("Job processed: {$job->displayName()} [{$job->jobId()}]");
        } catch (Throwable $e) {
            $this->logError("Job failed: {$job->displayName()} [{$job->jobId()}] - {$e->getMessage()}");

            $maxTries = $job->maxTries() ?? $maxTries;
            $attempts = $job->attempts();

            if ($maxTries > 0 && $attempts >= $maxTries) {
                $job->failed($e);
                $this->manager->logFailedJob($connection, $job->queue() ?? 'default', $job, $e);
                $job->delete();
                $this->logError("Job deleted after max tries: {$job->displayName()} [{$job->jobId()}]");
            } else {
                $backoff = $this->getBackoff($job, $options);
                $job->release($backoff);
                $this->logInfo("Job released for retry: {$job->displayName()} [{$job->jobId()}] (attempt {$attempts})");
            }
        }
    }

    protected function runJobWithMiddleware(JobInterface $job): void
    {
        $middleware = $job->middleware();

        if ($middleware === []) {
            $job->handle();
            return;
        }

        $pipeline = $middleware;
        $pipeline[] = function (object $job) {
            $job->handle();
        };

        $this->sendJobThroughPipeline($job, $pipeline);
    }

    protected function sendJobThroughPipeline(object $job, array $pipeline): void
    {
        $middleware = array_shift($pipeline);

        if ($middleware === null) {
            return;
        }

        if ($middleware instanceof \Closure) {
            $middleware($job, function ($nextJob) use ($pipeline) {
                $this->sendJobThroughPipeline($nextJob, $pipeline);
            });
            return;
        }

        if ($middleware instanceof JobMiddlewareInterface) {
            $middleware->handle($job, function ($nextJob) use ($pipeline) {
                $this->sendJobThroughPipeline($nextJob, $pipeline);
            });
            return;
        }

        if (is_string($middleware)) {
            $instance = app($middleware);
            if ($instance instanceof JobMiddlewareInterface) {
                $instance->handle($job, function ($nextJob) use ($pipeline) {
                    $this->sendJobThroughPipeline($nextJob, $pipeline);
                });
                return;
            }
        }

        throw new \RuntimeException('Invalid job middleware: ' . gettype($middleware));
    }

    protected function getBackoff(JobInterface $job, array $options): int
    {
        $backoff = $job->backoff() ?? [];

        if (!empty($backoff)) {
            $attempt = min($job->attempts(), count($backoff)) - 1;

            if (isset($backoff[$attempt])) {
                return $backoff[$attempt];
            }

            return end($backoff);
        }

        return $options['backoff'] ?? 0;
    }

    public function shouldQuit(): bool
    {
        return $this->shouldQuit;
    }

    public function stop(): void
    {
        $this->shouldQuit = true;
    }

    protected function memoryExceeded(int $memoryLimit): bool
    {
        return (memory_get_usage(true) / 1024 / 1024) >= $memoryLimit;
    }

    protected function sleep(int $seconds): void
    {
        sleep($seconds);
    }

    protected function logInfo(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->info($message);
        }
    }

    protected function logError(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->error($message);
        }
    }

    public function getManager(): QueueManager
    {
        return $this->manager;
    }
}
