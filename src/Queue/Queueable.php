<?php

declare(strict_types=1);

namespace Witals\Framework\Queue;

trait Queueable
{
    public ?string $queue = null;

    public ?string $connection = null;

    public ?int $timeout = null;

    public ?int $maxTries = null;

    public ?int $maxExceptions = null;

    public ?array $backoff = null;

    public ?int $delay = null;

    public ?string $uniqueId = null;

    public function onQueue(string $queue): static
    {
        $this->queue = $queue;

        return $this;
    }

    public function onConnection(string $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    public function timeout(?int $seconds): static
    {
        $this->timeout = $seconds;

        return $this;
    }

    public function maxTries(?int $tries): static
    {
        $this->maxTries = $tries;

        return $this;
    }

    public function maxExceptions(?int $exceptions): static
    {
        $this->maxExceptions = $exceptions;

        return $this;
    }

    public function backoff(array $backoff): static
    {
        $this->backoff = $backoff;

        return $this;
    }

    public function delay(?int $seconds): static
    {
        $this->delay = $seconds;

        return $this;
    }
}
