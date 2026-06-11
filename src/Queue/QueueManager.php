<?php

declare(strict_types=1);

namespace Witals\Framework\Queue;

use InvalidArgumentException;
use Witals\Framework\Queue\Contracts\QueueInterface;
use Witals\Framework\Queue\Contracts\QueueWorkerInterface;
use Witals\Framework\Queue\Drivers\DatabaseQueue;
use Witals\Framework\Queue\Drivers\RedisQueue;
use Witals\Framework\Queue\Drivers\SyncQueue;
use Witals\Framework\Queue\Drivers\NullQueue;
use Psr\Log\LoggerInterface;
use Witals\Framework\Queue\Contracts\FailedJobProviderInterface;

class QueueManager implements QueueInterface
{
    protected array $connections = [];

    protected array $config = [];

    protected string $default;

    protected ?LoggerInterface $logger = null;

    protected ?QueueWorkerInterface $worker = null;

    protected ?FailedJobProviderInterface $failedJobProvider = null;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->default = $config['default'] ?? 'sync';
    }

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setFailedJobProvider(?FailedJobProviderInterface $provider): void
    {
        $this->failedJobProvider = $provider;
    }

    public function getFailedJobProvider(): ?FailedJobProviderInterface
    {
        return $this->failedJobProvider;
    }

    public function logFailedJob(string $connection, string $queue, object $job, \Throwable $e): void
    {
        if ($this->failedJobProvider === null) {
            return;
        }

        $payload = serialize([
            'displayName' => method_exists($job, 'displayName') ? $job->displayName() : get_class($job),
            'job' => serialize($job),
        ]);

        $this->failedJobProvider->log($connection, $queue, $payload, $e);
    }

    public function connection(?string $name = null): QueueInterface
    {
        $name ??= $this->default;

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->resolve($name);
        }

        return $this->connections[$name];
    }

    protected function resolve(string $name): QueueInterface
    {
        $config = $this->config['connections'][$name] ?? ['driver' => $name];
        $driver = $config['driver'] ?? $name;

        return match ($driver) {
            'sync' => new SyncQueue(),
            'null' => new NullQueue(),
            'database' => $this->createDatabaseDriver($config),
            'redis' => $this->createRedisDriver($config),
            default => throw new InvalidArgumentException("Queue driver [{$driver}] not supported."),
        };
    }

    protected function createDatabaseDriver(array $config): DatabaseQueue
    {
        return new DatabaseQueue(
            connection: $config['connection'] ?? 'default',
            table: $config['table'] ?? 'jobs',
            queue: $config['queue'] ?? 'default',
            retryAfter: $config['retry_after'] ?? 90,
            afterCommit: $config['after_commit'] ?? false,
        );
    }

    protected function createRedisDriver(array $config): RedisQueue
    {
        if (!isset($config['client'])) {
            throw new InvalidArgumentException('Redis client is required for redis queue driver.');
        }

        return new RedisQueue(
            client: $config['client'],
            queue: $config['queue'] ?? 'default',
            retryAfter: $config['retry_after'] ?? 90,
            afterCommit: $config['after_commit'] ?? false,
            blockFor: $config['block_for'] ?? 5,
        );
    }

    public function setWorker(QueueWorkerInterface $worker): void
    {
        $this->worker = $worker;
    }

    public function getWorker(): QueueWorkerInterface
    {
        if ($this->worker === null) {
            $this->worker = new Worker($this);
        }

        return $this->worker;
    }

    public function push(object $job, ?string $queue = null): string
    {
        return $this->connection($job->connection ?? null)->push($job, $queue ?? $job->queue ?? null);
    }

    public function pushRaw(string $payload, ?string $queue = null): string
    {
        return $this->connection()->pushRaw($payload, $queue);
    }

    public function later(\DateTimeInterface|\DateInterval|int $delay, object $job, ?string $queue = null): string
    {
        return $this->connection($job->connection ?? null)->later($delay, $job, $queue ?? $job->queue ?? null);
    }

    public function pop(?string $queue = null): ?\Witals\Framework\Queue\Contracts\JobInterface
    {
        return $this->connection()->pop($queue);
    }

    public function bulk(array $jobs, ?string $queue = null): array
    {
        return $this->connection()->bulk($jobs, $queue);
    }

    public function getConnectionName(): string
    {
        return $this->default;
    }

    public function setConnectionName(string $name): void
    {
        $this->default = $name;
    }
}
