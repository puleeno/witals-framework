<?php

declare(strict_types=1);

namespace Witals\Framework\Auth\TokenStorage;

use Witals\Framework\Contracts\Auth\TokenInterface;
use Witals\Framework\Contracts\Auth\TokenStorageInterface;
use DateTimeInterface;
use Redis;

/**
 * Redis Token Storage
 * Requires ext-redis or similar client
 */
class RedisTokenStorage implements TokenStorageInterface
{
    public function __construct(
        protected Redis $redis,
        protected string $prefix = 'auth_token:'
    ) {
    }

    public function load(string $id): ?TokenInterface
    {
        $data = $this->redis->get($this->prefix . $id);
        
        if (!$data) {
            return null;
        }

        $decoded = json_decode($data, true);
        if (!$decoded) {
            return null;
        }

        $expiresAt = isset($decoded['expires_at']) ? new \DateTimeImmutable($decoded['expires_at']) : null;

        return new class($id, $decoded['payload'], $expiresAt) implements TokenInterface {
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
        $id = bin2hex(random_bytes(32));
        
        $data = [
            'payload' => $payload,
            'expires_at' => $expiresAt?->format(DateTimeInterface::ATOM),
            'created_at' => (new \DateTimeImmutable())->format(DateTimeInterface::ATOM)
        ];

        // Calculate TTL
        $ttl = $expiresAt ? $expiresAt->getTimestamp() - time() : 3600 * 24 * 7; // Default 7 days
        if ($ttl <= 0) $ttl = 60; // minimum safety

        $this->redis->setex($this->prefix . $id, $ttl, json_encode($data));

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
        $this->redis->del($this->prefix . $token->getID());
    }
}
