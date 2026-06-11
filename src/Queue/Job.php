<?php

declare(strict_types=1);

namespace Witals\Framework\Queue;

use Witals\Framework\Queue\Contracts\JobInterface;
use Witals\Framework\Queue\Contracts\ShouldQueue;
use Throwable;

abstract class Job implements ShouldQueue
{
    use Queueable;

    protected ?string $jobId = null;

    public function failed(Throwable $e): void
    {
    }

    abstract public function handle(): void;

    public function displayName(): string
    {
        return static::class;
    }

    public function jobId(): ?string
    {
        return $this->jobId;
    }

    public function setJobId(string $id): void
    {
        $this->jobId = $id;
    }
}
