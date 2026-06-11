<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Drivers;

use Witals\Framework\Queue\Contracts\QueueInterface;
use Witals\Framework\Queue\Contracts\JobInterface;
use Witals\Framework\Queue\QueueManager;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use DateTimeInterface;
use DateInterval;
use Throwable;

class DatabaseQueue implements QueueInterface
{
    protected string $connectionName;

    protected string $table;

    protected string $queue;

    protected int $retryAfter;

    protected bool $afterCommit;

    protected ?object $dbConnection = null;

    protected string $connection;

    public function __construct(
        string $connection = 'default',
        string $table = 'jobs',
        string $queue = 'default',
        int $retryAfter = 90,
        bool $afterCommit = false,
    ) {
        $this->connection = $connection;
        $this->table = $table;
        $this->queue = $queue;
        $this->retryAfter = $retryAfter;
        $this->afterCommit = $afterCommit;
    }

    public function push(object $job, ?string $queue = null): string
    {
        return $this->pushRaw($this->createPayload($job), $queue);
    }

    public function pushRaw(string $payload, ?string $queue = null): string
    {
        $queue ??= $this->queue;

        $data = [
            'queue' => $queue,
            'payload' => $payload,
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => time(),
            'created_at' => time(),
        ];

        $id = $this->getDatabase()->insert($this->table)->values($data)->run();

        return (string) $id;
    }

    public function later(DateTimeInterface|DateInterval|int $delay, object $job, ?string $queue = null): string
    {
        $queue ??= $this->queue;

        $availableAt = $this->availableAt($delay);

        $data = [
            'queue' => $queue,
            'payload' => $this->createPayload($job),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => $availableAt,
            'created_at' => time(),
        ];

        $id = $this->getDatabase()->insert($this->table)->values($data)->run();

        return (string) $id;
    }

    public function pop(?string $queue = null): ?JobInterface
    {
        $queue ??= $this->queue;

        $job = $this->getNextAvailableJob($queue);

        if ($job === null) {
            return null;
        }

        $this->markJobAsReserved($job->id);

        return new DatabaseQueueJob(
            $this,
            $job,
            $this->connectionName,
            $queue,
        );
    }

    public function bulk(array $jobs, ?string $queue = null): array
    {
        $queue ??= $this->queue;
        $ids = [];

        foreach ($jobs as $job) {
            $ids[] = $this->push($job, $queue);
        }

        return $ids;
    }

    protected function getNextAvailableJob(string $queue): ?object
    {
        $this->releaseReservedJobs($queue);

        $job = $this->getDatabase()
            ->select()
            ->from($this->table)
            ->where('queue', $queue)
            ->andWhere('reserved_at', 'is', null)
            ->andWhere('available_at', '<=', time())
            ->orderBy('id', 'asc')
            ->limit(1)
            ->fetchOne();

        return $job !== false ? $job : null;
    }

    protected function releaseReservedJobs(string $queue): void
    {
        $expired = time() - $this->retryAfter;

        $this->getDatabase()
            ->update($this->table, [
                'reserved_at' => null,
            ])
            ->where('queue', $queue)
            ->andWhere('reserved_at', '<=', $expired)
            ->run();
    }

    protected function markJobAsReserved(string|int $id): void
    {
        $this->getDatabase()
            ->update($this->table, [
                'reserved_at' => time(),
                'attempts' => $this->getDatabase()
                    ->select()
                    ->from($this->table)
                    ->where('id', $id)
                    ->fetchColumn('attempts') + 1,
            ])
            ->where('id', $id)
            ->run();
    }

    public function deleteReserved(string|int $id): void
    {
        $this->getDatabase()
            ->delete()
            ->from($this->table)
            ->where('id', $id)
            ->run();
    }

    public function releaseReserved(string|int $id, int $delay = 0): void
    {
        $availableAt = time() + $delay;

        $this->getDatabase()
            ->update($this->table, [
                'reserved_at' => null,
                'available_at' => $availableAt,
            ])
            ->where('id', $id)
            ->run();
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

    protected function getDatabase(): DatabaseInterface
    {
        if ($this->dbConnection === null) {
            $manager = app(DatabaseManager::class);
            $db = $manager->database($this->connection);
            $this->dbConnection = $db;

            if (!$this->tableExists()) {
                $this->createTable();
            }
        }

        return $this->dbConnection;
    }

    protected function tableExists(): bool
    {
        try {
            $schema = $this->dbConnection
                ->withTable($this->table)
                ->getSchema();

            return $schema->exists();
        } catch (Throwable) {
            return false;
        }
    }

    protected function createTable(): void
    {
        $schema = $this->dbConnection
            ->withTable($this->table)
            ->getSchema();

        if (!$schema->exists()) {
            $schema->primary('id');
            $schema->string('queue', 255);
            $schema->longText('payload');
            $schema->integer('attempts', false, false, 0);
            $schema->integer('reserved_at', true, true, null);
            $schema->integer('available_at', false, false, 0);
            $schema->integer('created_at', false, false, 0);
            $schema->index(['queue', 'reserved_at', 'available_at']);
            $schema->save();
        }
    }

    public function getConnectionName(): string
    {
        return $this->connectionName ?? 'database';
    }

    public function setConnectionName(string $name): void
    {
        $this->connectionName = $name;
    }

    public function getTable(): string
    {
        return $this->table;
    }
}
