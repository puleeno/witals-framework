<?php

declare(strict_types=1);

namespace Witals\Framework\Lifecycle;

use Witals\Framework\Contracts\LifecycleManager;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * ReactPHP Lifecycle Manager
 * Handles lifecycle hooks for ReactPHP runtime
 */
class ReactPhpLifecycle implements LifecycleManager
{
    private bool $booted = false;

    public function onBoot(): void
    {
        if ($this->booted) {
            return;
        }

        // ReactPHP-specific boot logic
        // Initialize event loop resources, if needed
        $this->booted = true;
    }

    public function onRequestStart(Request $request): void
    {
        // Called before each request in ReactPHP event loop
        // Can be used for request-specific initialization
    }

    public function onRequestEnd(Request $request, Response $response): void
    {
        // Called after each request
        // Clean up request-specific resources
    }

    public function onTerminate(): void
    {
        // In ReactPHP, this is called when the server shuts down
        // Not called after each request
    }

    public function getLifecycleType(): string
    {
        return 'reactphp';
    }

    public function isLongRunning(): bool
    {
        return true;
    }
}
