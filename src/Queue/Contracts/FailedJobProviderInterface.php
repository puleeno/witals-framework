<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Contracts;

interface FailedJobProviderInterface
{
    public function log(string $connection, string $queue, string $payload, \Throwable $exception): string;

    public function all(): array;

    public function find(string $id): ?array;

    public function forget(string $id): bool;

    public function flush(): int;
}
