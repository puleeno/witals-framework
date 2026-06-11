<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Drivers;

use Witals\Framework\Queue\Contracts\QueueInterface;
use Witals\Framework\Queue\Contracts\JobInterface;

class NullQueue implements QueueInterface
{
    protected string $connectionName;

    public function push(object $job, string $queue = null): string
    {
        return uniqid('null_', true);
    }

    public function pushRaw(string $payload, string $queue = null): string
    {
        return uniqid('null_', true);
    }

    public function later(\DateTimeInterface|\DateInterval|int $delay, object $job, string $queue = null): string
    {
        return uniqid('null_', true);
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

    public function getConnectionName(): string
    {
        return $this->connectionName ?? 'null';
    }

    public function setConnectionName(string $name): void
    {
        $this->connectionName = $name;
    }
}
