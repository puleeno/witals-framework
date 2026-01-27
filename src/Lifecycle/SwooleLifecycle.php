<?php

declare(strict_types=1);

namespace Witals\Framework\Lifecycle;

use Witals\Framework\Contracts\LifecycleManager;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * Swoole Lifecycle Manager
 * Handles lifecycle hooks for Swoole runtime
 */
class SwooleLifecycle implements LifecycleManager
{
    private bool $booted = false;

    public function onBoot(): void
    {
        if ($this->booted) {
            return;
        }

        // Swoole-specific boot logic
        // Initialize coroutine context, connection pools, etc.
        $this->booted = true;
    }

    public function onRequestStart(Request $request): void
    {
        // Called before each request in Swoole worker
        // Can be used for coroutine context initialization
    }

    public function onRequestEnd(Request $request, Response $response): void
    {
        // Called after each request
        // Clean up coroutine context and request-specific resources
    }

    public function onTerminate(): void
    {
        // In Swoole, this is called when the worker shuts down
        // Not called after each request
    }

    public function getLifecycleType(): string
    {
        return 'swoole';
    }

    public function isLongRunning(): bool
    {
        return true;
    }
}
