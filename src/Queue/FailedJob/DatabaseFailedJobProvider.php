<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\FailedJob;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Throwable;
use Witals\Framework\Queue\Contracts\FailedJobProviderInterface;

class DatabaseFailedJobProvider implements FailedJobProviderInterface
{
    protected string $table;

    protected ?DatabaseInterface $db = null;

    public function __construct(
        protected string $connection = 'default',
        string $table = 'failed_jobs',
    ) {
        $this->table = $table;
    }

    public function log(string $connection, string $queue, string $payload, Throwable $exception): string
    {
        $db = $this->db();

        $id = $db->insert($this->table)->values([
            'connection' => $connection,
            'queue' => $queue,
            'payload' => $payload,
            'exception' => (string) $exception,
            'failed_at' => date('Y-m-d H:i:s'),
        ])->run();

        return (string) $id;
    }

    public function all(): array
    {
        $db = $this->db();

        return iterator_to_array(
            $db->select()->from($this->table)->orderBy('id', 'desc')->getIterator()
        );
    }

    public function find(string $id): ?array
    {
        $db = $this->db();

        $row = $db->select()->from($this->table)->where('id', (int) $id)->fetchOne();

        return $row !== false ? (array) $row : null;
    }

    public function forget(string $id): bool
    {
        $db = $this->db();

        $deleted = $db->delete()->from($this->table)->where('id', (int) $id)->run();

        return $deleted > 0;
    }

    public function flush(): int
    {
        $db = $this->db();

        return $db->delete()->from($this->table)->run();
    }

    protected function db(): DatabaseInterface
    {
        if ($this->db === null) {
            $manager = app(DatabaseManager::class);
            $this->db = $manager->database($this->connection);

            if (!$this->tableExists()) {
                $this->createTable();
            }
        }

        return $this->db;
    }

    protected function tableExists(): bool
    {
        try {
            $schema = $this->db
                ->withTable($this->table)
                ->getSchema();

            return $schema->exists();
        } catch (Throwable) {
            return false;
        }
    }

    protected function createTable(): void
    {
        $schema = $this->db
            ->withTable($this->table)
            ->getSchema();

        if (!$schema->exists()) {
            $schema->primary('id');
            $schema->string('connection', 255);
            $schema->string('queue', 255);
            $schema->longText('payload');
            $schema->longText('exception');
            $schema->datetime('failed_at');
            $schema->index(['queue']);
            $schema->save();
        }
    }
}
