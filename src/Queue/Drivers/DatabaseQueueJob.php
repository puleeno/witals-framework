<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Drivers;

use Witals\Framework\Queue\Contracts\QueueInterface;
use Witals\Framework\Queue\Contracts\JobInterface;
use DateTimeInterface;
use DateInterval;
use Throwable;

class DatabaseQueueJob implements JobInterface
{
    protected bool $deleted = false;

    protected bool $released = false;

    protected bool $failed = false;

    public function __construct(
        protected DatabaseQueue $queue,
        protected object $job,
        protected string $connectionName,
        protected string $queueName,
    ) {
    }

    public function handle(): void
    {
        $payload = unserialize($this->job->payload);

        if ($payload === false || !isset($payload['job'])) {
            throw new \InvalidArgumentException('Invalid job payload.');
        }

        $instance = unserialize($payload['job']);

        if ($instance === false) {
            throw new \InvalidArgumentException('Invalid serialized job.');
        }

        $instance->handle();
    }

    public function failed(Throwable $e): void
    {
        $this->markAsFailed();

        $payload = unserialize($this->job->payload);

        if ($payload !== false && isset($payload['job'])) {
            $instance = unserialize($payload['job']);

            if ($instance !== false && method_exists($instance, 'failed')) {
                $instance->failed($e);
            }
        }
    }

    public function displayName(): string
    {
        $payload = unserialize($this->job->payload);

        return $payload['displayName'] ?? 'Unknown';
    }

    public function jobId(): ?string
    {
        return (string) $this->job->id;
    }

    public function queue(): ?string
    {
        return $this->queueName;
    }

    public function attempts(): int
    {
        return (int) ($this->job->attempts ?? 0);
    }

    public function markAsFailed(): void
    {
        $this->failed = true;
    }

    public function delete(): void
    {
        $this->queue->deleteReserved($this->job->id);
        $this->deleted = true;
    }

    public function release(int $delay = 0): void
    {
        $this->queue->releaseReserved($this->job->id, $delay);
        $this->released = true;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function isReleased(): bool
    {
        return $this->released;
    }

    public function hasFailed(): bool
    {
        return $this->failed;
    }

    public function getRawBody(): string
    {
        return $this->job->payload;
    }

    public function timeout(): ?int
    {
        $payload = unserialize($this->job->payload);

        if ($payload !== false && isset($payload['job'])) {
            $instance = unserialize($payload['job']);

            if ($instance !== false && isset($instance->timeout)) {
                return $instance->timeout;
            }
        }

        return null;
    }

    public function maxTries(): ?int
    {
        $payload = unserialize($this->job->payload);

        if ($payload !== false && isset($payload['job'])) {
            $instance = unserialize($payload['job']);

            if ($instance !== false && isset($instance->maxTries)) {
                return $instance->maxTries;
            }
        }

        return null;
    }

    public function maxExceptions(): ?int
    {
        $payload = unserialize($this->job->payload);

        if ($payload !== false && isset($payload['job'])) {
            $instance = unserialize($payload['job']);

            if ($instance !== false && isset($instance->maxExceptions)) {
                return $instance->maxExceptions;
            }
        }

        return null;
    }

    public function backoff(): ?array
    {
        $payload = unserialize($this->job->payload);

        if ($payload !== false && isset($payload['job'])) {
            $instance = unserialize($payload['job']);

            if ($instance !== false && isset($instance->backoff)) {
                return $instance->backoff;
            }
        }

        return null;
    }

    public function getJob(): object
    {
        return $this->job;
    }
}
