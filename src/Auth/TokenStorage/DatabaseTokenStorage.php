<?php

declare(strict_types=1);

namespace Witals\Framework\Auth\TokenStorage;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use Witals\Framework\Contracts\Auth\TokenInterface;
use Witals\Framework\Contracts\Auth\TokenStorageInterface;
use DateTimeInterface;

class DatabaseTokenStorage implements TokenStorageInterface
{
    protected string $table = 'auth_tokens';

    public function __construct(
        protected DatabaseProviderInterface $dbal
    ) {
    }

    protected function db(): DatabaseInterface
    {
        return $this->dbal->database();
    }

    public function load(string $id): ?TokenInterface
    {
        // Lazy create table if not exists (Dev convenience)
        // In prod, use migrations
        if (!$this->db()->hasTable($this->table)) {
            $this->createTable();
        }

        $row = $this->db()->table($this->table)->select()->where('id', $id)->run()->fetch();

        if (!$row) {
            return null;
        }

        // Check expiration
        if (!empty($row['expires_at'])) {
            $expiresAt = new \DateTimeImmutable($row['expires_at']);
            if ($expiresAt < new \DateTimeImmutable()) {
                $this->deleteById($id);
                return null;
            }
        }

        return new class($id, json_decode($row['payload'], true), isset($expiresAt) ? $expiresAt : null) implements TokenInterface {
            public function __construct(
                private string $id,
                private array $payload,
                private ?DateTimeInterface $expiresAt
            ) {}

            public function getID(): string { return $this->id; }
            public function getPayload(): array { return $this->payload; }
            public function getExpiresAt(): ?DateTimeInterface { return $this->expiresAt; }
        };
    }

    public function create(array $payload, DateTimeInterface $expiresAt = null): TokenInterface
    {
        if (!$this->db()->hasTable($this->table)) {
            $this->createTable();
        }

        $id = bin2hex(random_bytes(32));
        
        $this->db()->table($this->table)->insertOne([
            'id' => $id,
            'payload' => json_encode($payload),
            'expires_at' => $expiresAt?->format('Y-m-d H:i:s'),
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
        ]);

        return new class($id, $payload, $expiresAt) implements TokenInterface {
            public function __construct(
                private string $id,
                private array $payload,
                private ?DateTimeInterface $expiresAt
            ) {}

            public function getID(): string { return $this->id; }
            public function getPayload(): array { return $this->payload; }
            public function getExpiresAt(): ?DateTimeInterface { return $this->expiresAt; }
        };
    }

    public function delete(TokenInterface $token): void
    {
        $this->deleteById($token->getID());
    }

    protected function deleteById(string $id): void
    {
         if ($this->db()->hasTable($this->table)) {
            $this->db()->table($this->table)->delete(['id' => $id])->run();
         }
    }

    protected function createTable(): void
    {
        $schema = $this->db()->table($this->table)->getSchema();
        $schema->primary('id')->string(64);
        $schema->column('payload')->text();
        $schema->column('expires_at')->datetime()->nullable();
        $schema->column('created_at')->datetime();
        $schema->save();
    }
}
