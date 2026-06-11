<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Drivers;

use Witals\Framework\Queue\Contracts\JobInterface;
use Witals\Framework\Queue\Contracts\QueueInterface;
use Throwable;

class RedisQueueJob implements JobInterface
{
    protected bool $deleted = false;

    protected bool $released = false;

    protected bool $failed = false;

    protected ?int $attempts = null;

    public function __construct(
        protected RedisQueue $queue,
        protected string $payload,
        protected string $connectionName,
        protected string $queueName,
    ) {
    }

    public function handle(): void
    {
        $data = unserialize($this->payload);

        if ($data === false || !isset($data['job'])) {
            throw new \InvalidArgumentException('Invalid job payload.');
        }

        $instance = unserialize($data['job']);

        if ($instance === false) {
            throw new \InvalidArgumentException('Invalid serialized job.');
        }

        $instance->handle();
    }

    public function failed(Throwable $e): void
    {
        $this->markAsFailed();

        $data = unserialize($this->payload);

        if ($data !== false && isset($data['job'])) {
            $instance = unserialize($data['job']);

            if ($instance !== false && method_exists($instance, 'failed')) {
                $instance->failed($e);
            }
        }
    }

    public function displayName(): string
    {
        $data = unserialize($this->payload);

        return $data['displayName'] ?? 'Unknown';
    }

    public function jobId(): ?string
    {
        return md5($this->payload);
    }

    public function queue(): ?string
    {
        return $this->queueName;
    }

    public function attempts(): int
    {
        if ($this->attempts !== null) {
            return $this->attempts;
        }

        return 1;
    }

    public function setAttempts(int $attempts): void
    {
        $this->attempts = $attempts;
    }

    public function markAsFailed(): void
    {
        $this->failed = true;
    }

    public function delete(): void
    {
        $this->queue->deleteReserved($this->payload);
        $this->deleted = true;
    }

    public function release(int $delay = 0): void
    {
        $this->queue->releaseReserved($this->payload, $delay);
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
        return $this->payload;
    }

    public function timeout(): ?int
    {
        $data = unserialize($this->payload);

        if ($data !== false && isset($data['job'])) {
            $instance = unserialize($data['job']);

            if ($instance !== false && isset($instance->timeout)) {
                return $instance->timeout;
            }
        }

        return null;
    }

    public function maxTries(): ?int
    {
        $data = unserialize($this->payload);

        if ($data !== false && isset($data['job'])) {
            $instance = unserialize($data['job']);

            if ($instance !== false && isset($instance->maxTries)) {
                return $instance->maxTries;
            }
        }

        return null;
    }

    public function maxExceptions(): ?int
    {
        $data = unserialize($this->payload);

        if ($data !== false && isset($data['job'])) {
            $instance = unserialize($data['job']);

            if ($instance !== false && isset($instance->maxExceptions)) {
                return $instance->maxExceptions;
            }
        }

        return null;
    }

    public function backoff(): ?array
    {
        $data = unserialize($this->payload);

        if ($data !== false && isset($data['job'])) {
            $instance = unserialize($data['job']);

            if ($instance !== false && isset($instance->backoff)) {
                return $instance->backoff;
            }
        }

        return null;
    }

    public function middleware(): array
    {
        $data = unserialize($this->payload);

        if ($data !== false && isset($data['job'])) {
            $instance = unserialize($data['job']);

            if ($instance !== false && method_exists($instance, 'middleware')) {
                return $instance->middleware();
            }
        }

        return [];
    }
}
