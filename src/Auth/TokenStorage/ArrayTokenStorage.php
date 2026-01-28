<?php

declare(strict_types=1);

namespace Witals\Framework\Auth\TokenStorage;

use Witals\Framework\Contracts\Auth\TokenStorageInterface;
use Witals\Framework\Contracts\Auth\TokenInterface;
use Witals\Framework\Auth\Token;
use DateTimeInterface;

/**
 * Array Token Storage
 * USE ONLY FOR TESTING OR NON-PERSISTENT WORKERS
 */
class ArrayTokenStorage implements TokenStorageInterface
{
    protected array $tokens = [];

    public function load(string $id): ?TokenInterface
    {
        return $this->tokens[$id] ?? null;
    }

    public function create(array $payload, DateTimeInterface $expiresAt = null): TokenInterface
    {
        // Simple random ID
        $id = bin2hex(random_bytes(32));
        $token = new Token($id, $payload, $expiresAt);
        
        $this->tokens[$id] = $token;
        
        return $token;
    }

    public function delete(TokenInterface $token): void
    {
        if (isset($this->tokens[$token->getID()])) {
            unset($this->tokens[$token->getID()]);
        }
    }
}
