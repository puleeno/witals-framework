<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Contracts;

interface QueueWorkerInterface
{
    /**
     * @param string $connection
     * @param string $queue
     * @param array{
     *     sleep?: int,
     *     max_tries?: int,
     *     max_exceptions?: int,
     *     backoff?: int,
     *     timeout?: int,
     *     memory?: int,
     *     rest?: int,
     * } $options
     */
    public function daemon(string $connection, string $queue, array $options = []): void;

    public function runJob(JobInterface $job, string $connection, array $options = []): void;

    public function getManager(): \Witals\Framework\Queue\QueueManager;

    public function shouldQuit(): bool;
}
