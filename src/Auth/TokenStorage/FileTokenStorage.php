<?php

declare(strict_types=1);

namespace Witals\Framework\Auth\TokenStorage;

use Witals\Framework\Contracts\Auth\TokenInterface;
use Witals\Framework\Contracts\Auth\TokenStorageInterface;
use DateTimeInterface;

class FileTokenStorage implements TokenStorageInterface
{
    public function __construct(
        protected string $directory
    ) {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    public function load(string $id): ?TokenInterface
    {
        $file = $this->getFilePath($id);
        if (!file_exists($file)) {
            return null;
        }

        $data = json_decode(file_get_contents($file), true);
        if (!$data) {
            return null;
        }

        // Check expiration
        if (isset($data['expiresAt'])) {
            $expiresAt = new \DateTimeImmutable($data['expiresAt']);
            if ($expiresAt < new \DateTimeImmutable()) {
                unlink($file);
                return null;
            }
        }

        return new class($id, $data['payload'], isset($data['expiresAt']) ? new \DateTimeImmutable($data['expiresAt']) : null) implements TokenInterface {
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
            'id' => $id,
            'payload' => $payload,
            'expiresAt' => $expiresAt?->format(DateTimeInterface::ATOM)
        ];

        file_put_contents($this->getFilePath($id), json_encode($data));

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
        $file = $this->getFilePath($token->getID());
        if (file_exists($file)) {
            unlink($file);
        }
    }

    protected function getFilePath(string $id): string
    {
        return $this->directory . '/' . $id . '.json';
    }
}
