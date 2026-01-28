<?php

declare(strict_types=1);

namespace Witals\Framework\Lifecycle;

use Witals\Framework\Contracts\LifecycleManager;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * Traditional Lifecycle Manager
 * For short-lived, request-per-process environments
 * 
 * Lifecycle: Boot -> RequestStart -> Handle -> RequestEnd -> Terminate
 * Each request creates a new PHP process
 */
class TraditionalLifecycle implements LifecycleManager
{
    protected array $bootedServices = [];
    protected float $bootTime;
    protected float $requestStartTime;

    public function onBoot(): void
    {
        $this->bootTime = microtime(true);

        // Boot services that need initialization
        // In traditional mode, this happens on EVERY request
        $this->bootServices();
    }

    public function onRequestStart(Request $request): void
    {
        $this->requestStartTime = microtime(true);

        // Request-specific initialization
        // Set up session, CSRF tokens, etc.
        $this->initializeRequest($request);
    }

    public function onRequestEnd(Request $request, Response $response): void
    {
        $duration = microtime(true) - $this->requestStartTime;

        // Log request metrics
        $this->logRequest($request, $response, $duration);

        // Cleanup request-specific resources
        $this->cleanupRequest();
    }

    public function onTerminate(): void
    {
        // Final cleanup before process dies
        // Close database connections, flush logs, etc.
        $this->shutdownServices();

        // In traditional mode, the process will die after this
        // so we don't need to worry about memory leaks
    }

    public function getLifecycleType(): string
    {
        return 'traditional';
    }

    public function isLongRunning(): bool
    {
        return false;
    }

    /**
     * Boot application services
     */
    protected function bootServices(): void
    {
        // Boot services needed for this request
        // Examples: Database, Cache, Session, etc.

        // Note: In traditional mode, we boot fresh every time
        // so we don't need to worry about stale state
    }

    /**
     * Initialize request-specific resources
     */
    protected function initializeRequest(Request $request): void
    {
        // Start session if needed
        // Initialize CSRF protection
        // Set up request context
    }

    /**
     * Log request information
     */
    protected function logRequest(Request $request, Response $response, float $duration): void
    {
        // Log request details
        // Can be overridden for custom logging
    }

    /**
     * Cleanup request resources
     */
    protected function cleanupRequest(): void
    {
        // Close temporary files
        // Clear request-specific caches
    }

    /**
     * Shutdown services before process termination
     */
    protected function shutdownServices(): void
    {
        // Close database connections
        // Flush logs
        // Send any pending data
    }

    /**
     * Get statistics about the worker process
     */
    public function getWorkerStats(): array
    {
        return [
            'uptime' => defined('WITALS_START') ? microtime(true) - WITALS_START : (microtime(true) - $this->bootTime),
            'requests_handled' => 1, // Traditional mode is always 1 request per process
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
        ];
    }
}
