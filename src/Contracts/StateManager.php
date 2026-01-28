<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts;

/**
 * State Manager Contract
 * Defines interface for managing application state
 */
interface StateManager
{
    /**
     * Set a state value
     */
    public function set(string $key, mixed $value): void;

    /**
     * Get a state value
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if state exists
     */
    public function has(string $key): bool;

    /**
     * Remove a state value
     */
    public function forget(string $key): void;

    /**
     * Clear all state
     */
    public function clear(): void;

    /**
     * Get all state data
     */
    public function all(): array;

    /**
     * Check if this is a stateful manager
     */
    public function isStateful(): bool;

    /**
     * Set a value that persists across requests
     * (In stateless mode, this may behave like set())
     */
    public function setPersistent(string $key, mixed $value): void;

    /**
     * Get only persistent state
     * (In stateless mode, this may behave like get())
     */
    public function getPersistent(string $key, mixed $default = null): mixed;

    /**
     * Get statistics about state usage
     * 
     * @return array{request_state_count: int, persistent_state_count: int, memory: int, ...}
     */
    public function getStats(): array;
}
