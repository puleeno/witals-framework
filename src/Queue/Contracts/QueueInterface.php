<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Contracts;

interface QueueInterface
{
    public function push(object $job, string $queue = null): string;

    public function pushRaw(string $payload, string $queue = null): string;

    public function later(\DateTimeInterface|\DateInterval|int $delay, object $job, string $queue = null): string;

    public function pop(string $queue = null): ?JobInterface;

    public function bulk(array $jobs, string $queue = null): array;

    public function getConnectionName(): string;

    public function setConnectionName(string $name): void;
}
