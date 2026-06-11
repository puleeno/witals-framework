<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Contracts;

interface JobInterface
{
    public function handle(): void;

    public function failed(\Throwable $e): void;

    public function displayName(): string;

    public function jobId(): ?string;

    public function queue(): ?string;

    public function attempts(): int;

    public function markAsFailed(): void;

    public function delete(): void;

    public function release(int $delay = 0): void;

    public function isDeleted(): bool;

    public function isReleased(): bool;

    public function hasFailed(): bool;

    public function getRawBody(): string;

    public function timeout(): ?int;

    public function maxTries(): ?int;

    public function maxExceptions(): ?int;

    public function backoff(): ?array;
}
