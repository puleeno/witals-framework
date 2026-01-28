<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts\Auth;

use DateTimeInterface;

interface TokenInterface
{
    /**
     * Get unique token ID.
     */
    public function getID(): string;

    /**
     * Get token payload.
     */
    public function getPayload(): array;

    /**
     * Get token expiration time.
     */
    public function getExpiresAt(): ?DateTimeInterface;
}
