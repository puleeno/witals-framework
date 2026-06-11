<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Drivers;

use Witals\Framework\Queue\Contracts\QueueInterface;
use Witals\Framework\Queue\Contracts\JobInterface;
use DateTimeInterface;
use DateInterval;

class RedisQueue implements QueueInterface
{
    protected string $connectionName;

    public function __construct(
        protected object $client,
        protected string $queue = 'default',
        protected int $retryAfter = 90,
        protected bool $afterCommit = false,
        protected int $blockFor = 5,
    ) {
    }

    public function push(object $job, string $queue = null): string
    {
        return $this->pushRaw($this->createPayload($job), $queue);
    }

    public function pushRaw(string $payload, string $queue = null): string
    {
        $queue ??= $this->queue;

        $this->client->rpush("queues:{$queue}", $payload);

        return uniqid('redis_', true);
    }

    public function later(DateTimeInterface|DateInterval|int $delay, object $job, string $queue = null): string
    {
        $queue ??= $this->queue;

        $payload = $this->createPayload($job);
        $availableAt = $this->availableAt($delay);

        $this->client->zadd("queues:{$queue}:delayed", $availableAt, $payload);

        return uniqid('redis_', true);
    }

    public function pop(string $queue = null): ?JobInterface
    {
        $queue ??= $this->queue;

        $this->migrateExpiredJobs($queue);

        $result = $this->client->blpop("queues:{$queue}", $this->blockFor);

        if ($result === null || $result === false) {
            return null;
        }

        if (is_array($result)) {
            $payload = $result[1] ?? null;
        } else {
            return null;
        }

        if ($payload === null) {
            return null;
        }

        return new RedisQueueJob(
            $this,
            $payload,
            $this->connectionName,
            $queue,
        );
    }

    public function bulk(array $jobs, string $queue = null): array
    {
        $queue ??= $this->queue;
        $ids = [];

        foreach ($jobs as $job) {
            $ids[] = $this->push($job, $queue);
        }

        return $ids;
    }

    public function deleteReserved(string $payload): void
    {
    }

    public function releaseReserved(string $payload, int $delay = 0): void
    {
        $queue = $this->queue;

        if ($delay > 0) {
            $availableAt = $this->availableAt($delay);
            $this->client->zadd("queues:{$queue}:delayed", $availableAt, $payload);
        } else {
            $this->client->rpush("queues:{$queue}", $payload);
        }
    }

    protected function migrateExpiredJobs(string $queue): void
    {
        $now = time();

        $expired = $this->client->zrangebyscore("queues:{$queue}:delayed", 0, $now);

        if (!empty($expired)) {
            foreach ($expired as $payload) {
                $this->client->rpush("queues:{$queue}", $payload);
            }

            $this->client->zremrangebyscore("queues:{$queue}:delayed", 0, $now);
        }
    }

    protected function createPayload(object $job): string
    {
        $payload = [
            'displayName' => method_exists($job, 'displayName') ? $job->displayName() : get_class($job),
            'job' => serialize(clone $job),
        ];

        return serialize($payload);
    }

    protected function availableAt(DateTimeInterface|DateInterval|int $delay): int
    {
        if (is_int($delay)) {
            return time() + $delay;
        }

        if ($delay instanceof DateInterval) {
            return (new \DateTimeImmutable())->add($delay)->getTimestamp();
        }

        return $delay->getTimestamp();
    }

    public function getConnectionName(): string
    {
        return $this->connectionName ?? 'redis';
    }

    public function setConnectionName(string $name): void
    {
        $this->connectionName = $name;
    }
}
