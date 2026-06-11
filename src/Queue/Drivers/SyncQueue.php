<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Drivers;

use Witals\Framework\Queue\Contracts\QueueInterface;
use Witals\Framework\Queue\Contracts\JobInterface;

class SyncQueue implements QueueInterface
{
    protected string $connectionName;

    public function push(object $job, string $queue = null): string
    {
        $jobId = uniqid('sync_', true);

        $this->executeJob($job);

        return $jobId;
    }

    public function pushRaw(string $payload, string $queue = null): string
    {
        $job = unserialize($payload);

        if ($job === false) {
            throw new \InvalidArgumentException('Unable to unserialize job payload.');
        }

        return $this->push($job, $queue);
    }

    public function later(\DateTimeInterface|\DateInterval|int $delay, object $job, string $queue = null): string
    {
        return $this->push($job, $queue);
    }

    public function pop(string $queue = null): ?JobInterface
    {
        return null;
    }

    public function bulk(array $jobs, string $queue = null): array
    {
        $ids = [];

        foreach ($jobs as $job) {
            $ids[] = $this->push($job, $queue);
        }

        return $ids;
    }

    protected function executeJob(object $job): void
    {
        if (method_exists($job, 'handle')) {
            $job->handle();
        }
    }

    public function getConnectionName(): string
    {
        return $this->connectionName ?? 'sync';
    }

    public function setConnectionName(string $name): void
    {
        $this->connectionName = $name;
    }
}
