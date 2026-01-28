<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts\Auth;

use DateTimeInterface;

interface TokenStorageInterface
{
    /**
     * Load token by ID.
     */
    public function load(string $id): ?TokenInterface;

    /**
     * Create a new token with payload.
     */
    public function create(array $payload, DateTimeInterface $expiresAt = null): TokenInterface;

    /**
     * Delete token.
     */
    public function delete(TokenInterface $token): void;
}
