<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts\Auth;

interface AuthContextInterface
{
    /**
     * Start authentication context with token and optional actor.
     */
    public function start(TokenInterface $token, ?object $actor = null): void;

    /**
     * Get current token.
     */
    public function getToken(): ?TokenInterface;

    /**
     * Get current actor.
     */
    public function getActor(): ?object;

    /**
     * Logout/Close the context (invalidate token).
     */
    public function close(): void;

    /**
     * Check if context is closed.
     */
    public function isClosed(): bool;
}
