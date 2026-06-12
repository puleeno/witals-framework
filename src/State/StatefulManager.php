<?php

declare(strict_types=1);

namespace Witals\Framework\State;

use Witals\Framework\Contracts\StateManager;

/**
 * Stateful State Manager
 * For RoadRunner where workers persist between requests
 * Manages both persistent state and request-scoped state
 */
class StatefulManager implements StateManager
{
    /**
     * Persistent state - survives across requests
     */
    protected static array $persistentState = [];

    /**
     * TTL timestamps for persistent keys: [key => expires_at]
     */
    protected static array $persistentTtl = [];

    /**
     * Default TTL for persistent state (null = never expires)
     */
    protected ?int $defaultPersistentTtl = 3600;

    /**
     * Request-scoped state - cleared after each request
     */
    protected array $requestState = [];

    /**
     * Keys that should persist across requests
     */
    protected array $persistentKeys = [];

    public function set(string $key, mixed $value): void
    {
        $this->requestState[$key] = $value;
    }

    /**
     * Set a value that persists across requests
     */
    public function setPersistent(string $key, mixed $value, ?int $ttl = null): void
    {
        self::$persistentState[$key] = $value;
        $this->persistentKeys[$key] = true;

        if ($ttl !== null || $this->defaultPersistentTtl !== null) {
            $expires = time() + ($ttl ?? $this->defaultPersistentTtl);
            self::$persistentTtl[$key] = $expires;
        } else {
            unset(self::$persistentTtl[$key]);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        // Check request state first
        if (array_key_exists($key, $this->requestState)) {
            return $this->requestState[$key];
        }

        // Then check persistent state (with TTL expiry)
        if (array_key_exists($key, self::$persistentState)) {
            $this->expireIfNeeded($key);
            if (array_key_exists($key, self::$persistentState)) {
                return self::$persistentState[$key];
            }
        }

        return $default;
    }

    /**
     * Get only persistent state
     */
    public function getPersistent(string $key, mixed $default = null): mixed
    {
        $this->expireIfNeeded($key);
        return self::$persistentState[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        if (array_key_exists($key, $this->requestState)) {
            return true;
        }

        $this->expireIfNeeded($key);
        return array_key_exists($key, self::$persistentState);
    }

    /**
     * Check if key exists in persistent state
     */
    public function hasPersistent(string $key): bool
    {
        $this->expireIfNeeded($key);
        return array_key_exists($key, self::$persistentState);
    }

    public function forget(string $key): void
    {
        unset($this->requestState[$key]);
        unset(self::$persistentState[$key], self::$persistentTtl[$key]);
        unset($this->persistentKeys[$key]);
    }

    /**
     * Forget only from request state
     */
    public function forgetRequest(string $key): void
    {
        unset($this->requestState[$key]);
    }

    /**
     * Forget only from persistent state
     */
    public function forgetPersistent(string $key): void
    {
        unset(self::$persistentState[$key]);
        unset($this->persistentKeys[$key]);
    }

    public function clear(): void
    {
        $this->requestState = [];
    }

    /**
     * Clear all persistent state
     * WARNING: This affects all future requests
     */
    public function clearPersistent(): void
    {
        self::$persistentState = [];
        $this->persistentKeys = [];
    }

    /**
     * Clear everything (both request and persistent)
     */
    public function clearAll(): void
    {
        $this->clear();
        $this->clearPersistent();
    }

    public function all(): array
    {
        return array_merge(self::$persistentState, $this->requestState);
    }

    /**
     * Get only request-scoped state
     */
    public function allRequest(): array
    {
        return $this->requestState;
    }

    /**
     * Get only persistent state
     */
    public function allPersistent(): array
    {
        return self::$persistentState;
    }

    public function isStateful(): bool
    {
        return true;
    }

    /**
     * Clean up after request
     * Called by Application after each request in RoadRunner mode
     */
    public function afterRequest(): void
    {
        // Clear request-scoped state
        $this->requestState = [];

        // Optionally run garbage collection on persistent state
        // to prevent memory leaks
        $this->garbageCollect();
    }

    /**
     * Garbage collection for persistent state
     * Override this method to implement custom cleanup logic
     */
    protected function garbageCollect(): void
    {
        // Remove expired TTL entries
        $now = time();
        foreach (self::$persistentTtl as $key => $expires) {
            if ($expires <= $now) {
                unset(self::$persistentState[$key], self::$persistentTtl[$key], $this->persistentKeys[$key]);
            }
        }

        // Limit persistent state size
        if (count(self::$persistentState) > 1000) {
            self::$persistentState = array_slice(self::$persistentState, -800, null, true);
        }
    }

    /**
     * Remove a persistent key if its TTL has expired
     */
    protected function expireIfNeeded(string $key): void
    {
        if (!isset(self::$persistentTtl[$key])) {
            return;
        }

        if (self::$persistentTtl[$key] <= time()) {
            unset(self::$persistentState[$key], self::$persistentTtl[$key], $this->persistentKeys[$key]);
        }
    }

    /**
     * Get statistics about state usage
     */
    public function getStats(): array
    {
        return [
            'request_state_count' => count($this->requestState),
            'persistent_state_count' => count(self::$persistentState),
            'request_memory' => strlen(serialize($this->requestState)),
            'persistent_memory' => strlen(serialize(self::$persistentState)),
            'total_memory' => memory_get_usage(true),
        ];
    }
}
