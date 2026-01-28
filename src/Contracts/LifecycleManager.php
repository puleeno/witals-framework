<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * Lifecycle Manager Contract
 * Defines hooks for different stages of application lifecycle
 */
interface LifecycleManager
{
    /**
     * Called once when application boots
     * - Traditional: Every request
     * - RoadRunner: Once per worker
     */
    public function onBoot(): void;

    /**
     * Called before handling a request
     * - Traditional: After boot, before handle
     * - RoadRunner: Before each request in the loop
     */
    public function onRequestStart(Request $request): void;

    /**
     * Called after handling a request
     * - Traditional: After handle, before terminate
     * - RoadRunner: After each request in the loop
     */
    public function onRequestEnd(Request $request, Response $response): void;

    /**
     * Called when application terminates
     * - Traditional: After every request
     * - RoadRunner: Never (or on worker shutdown)
     */
    public function onTerminate(): void;

    /**
     * Get lifecycle type
     */
    public function getLifecycleType(): string;

    /**
     * Check if this is a long-running lifecycle
     */
    public function isLongRunning(): bool;

    /**
     * Get statistics about the worker process
     * 
     * @return array{uptime: float, requests_handled: int, memory_usage: int, ...}
     */
    public function getWorkerStats(): array;
}
