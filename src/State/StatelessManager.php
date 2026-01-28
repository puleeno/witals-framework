<?php

declare(strict_types=1);

namespace Witals\Framework\State;

use Witals\Framework\Contracts\StateManager;

/**
 * Stateless State Manager
 * For traditional web servers where state doesn't persist between requests
 * State is stored per-request only
 */
class StatelessManager implements StateManager
{
    protected array $state = [];

    public function set(string $key, mixed $value): void
    {
        $this->state[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->state[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->state);
    }

    public function forget(string $key): void
    {
        unset($this->state[$key]);
    }

    public function clear(): void
    {
        $this->state = [];
    }

    public function all(): array
    {
        return $this->state;
    }

    public function isStateful(): bool
    {
        return false;
    }

    public function setPersistent(string $key, mixed $value): void
    {
        // In stateless mode, persistent state is just request state
        $this->set($key, $value);
    }

    public function getPersistent(string $key, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }

    /**
     * Get statistics about state usage
     */
    public function getStats(): array
    {
        return [
            'request_state_count' => count($this->state),
            'persistent_state_count' => 0,
            'total_memory' => memory_get_usage(true),
        ];
    }

    /**
     * Destructor - automatically clears state
     * In stateless mode, state is cleared after each request
     */
    public function __destruct()
    {
        $this->clear();
    }
}
