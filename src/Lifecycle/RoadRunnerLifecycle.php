<?php

declare(strict_types=1);

namespace Witals\Framework\Lifecycle;

use Witals\Framework\Contracts\LifecycleManager;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * RoadRunner Lifecycle Manager
 * For long-running worker processes
 * 
 * Lifecycle: 
 * - Boot (once per worker)
 * - Loop: RequestStart -> Handle -> RequestEnd (many times)
 * - Terminate (on worker shutdown, rarely)
 */
class RoadRunnerLifecycle implements LifecycleManager
{
    protected array $bootedServices = [];
    protected float $workerBootTime;
    protected int $requestCount = 0;
    protected array $requestMetrics = [];
    protected int $maxRequestsBeforeRestart = 1000;
    protected int $maxMemoryBeforeRestart;

    public function __construct()
    {
        // Set max memory to 80% of memory_limit
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $this->maxMemoryBeforeRestart = (int) ($memoryLimit * 0.8);
    }

    public function onBoot(): void
    {
        $this->workerBootTime = microtime(true);

        // Boot services ONCE for the entire worker lifetime
        // This is a key optimization in RoadRunner
        $this->bootServices();

        // Optimize for long-running process
        $this->optimizeForLongRunning();
    }

    public function onRequestStart(Request $request): void
    {
        $this->requestCount++;

        // Reset request-specific state
        // CRITICAL: Must clear any state from previous request
        $this->resetRequestState();

        // Initialize fresh request context
        $this->initializeRequest($request);

        // Check if worker should restart
        $this->checkWorkerHealth();
    }

    public function onRequestEnd(Request $request, Response $response): void
    {
        // Track metrics
        $this->trackRequestMetrics($request, $response);

        // Cleanup request-specific resources
        // CRITICAL: Must cleanup to prevent memory leaks
        $this->cleanupRequest();

        // Run garbage collection periodically
        if ($this->requestCount % 10 === 0) {
            gc_collect_cycles();
        }

        // Clear opcode cache for changed files (in dev mode)
        if (getenv('APP_ENV') === 'development') {
            $this->clearOpcacheIfNeeded();
        }
    }

    public function onTerminate(): void
    {
        // Worker is shutting down (rare)
        // Log worker statistics
        $this->logWorkerStats();

        // Graceful shutdown of services
        $this->shutdownServices();
    }

    public function getLifecycleType(): string
    {
        return 'roadrunner';
    }

    public function isLongRunning(): bool
    {
        return true;
    }

    /**
     * Get worker statistics
     */
    public function getWorkerStats(): array
    {
        return [
            'uptime' => microtime(true) - $this->workerBootTime,
            'requests_handled' => $this->requestCount,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'average_request_time' => $this->getAverageRequestTime(),
        ];
    }

    /**
     * Boot services once for worker lifetime
     */
    protected function bootServices(): void
    {
        // Boot services that will be reused across requests
        // Examples:
        // - Database connection pool
        // - Compiled routes/views
        // - Application configuration
        // - Dependency injection container

        // These services stay in memory and are reused
    }

    /**
     * Optimize PHP for long-running process
     */
    protected function optimizeForLongRunning(): void
    {
        // Enable garbage collection
        gc_enable();

        // Disable session auto-start (we'll manage it per-request)
        ini_set('session.auto_start', '0');

        // Increase memory limit if needed
        // ini_set('memory_limit', '256M');
    }

    /**
     * Reset state between requests
     * CRITICAL for preventing state leakage
     */
    protected function resetRequestState(): void
    {
        // Clear global state
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_COOKIE = [];
        $_REQUEST = [];

        // Reset error handlers
        // Clear any request-specific singletons
        // Reset static variables in services
    }

    /**
     * Initialize request-specific resources
     */
    protected function initializeRequest(Request $request): void
    {
        // Set up fresh request context
        // Initialize session for this request
        // Set up CSRF tokens
    }

    /**
     * Check worker health and decide if restart is needed
     */
    protected function checkWorkerHealth(): void
    {
        // Check if worker should restart
        $currentMemory = memory_get_usage(true);

        if ($currentMemory > $this->maxMemoryBeforeRestart) {
            // Log warning about high memory usage
            error_log(sprintf(
                '[RoadRunner Worker] High memory usage: %s (limit: %s)',
                $this->formatBytes($currentMemory),
                $this->formatBytes($this->maxMemoryBeforeRestart)
            ));

            // RoadRunner will restart worker based on max_jobs or memory limits
        }

        if ($this->requestCount >= $this->maxRequestsBeforeRestart) {
            // Worker has handled max requests
            // RoadRunner will restart it
        }
    }

    /**
     * Track request metrics
     */
    protected function trackRequestMetrics(Request $request, Response $response): void
    {
        $this->requestMetrics[] = [
            'timestamp' => time(),
            'duration' => defined('WITALS_START') ? microtime(true) - WITALS_START : 0,
            'memory' => memory_get_usage(true),
            'status' => $response->getStatusCode(),
        ];

        // Keep only last 100 requests
        if (count($this->requestMetrics) > 100) {
            $this->requestMetrics = array_slice($this->requestMetrics, -100);
        }
    }

    /**
     * Cleanup request resources
     */
    protected function cleanupRequest(): void
    {
        // Close request-specific resources
        // Clear temporary files
        // Reset request-scoped services

        // IMPORTANT: Don't close persistent connections (DB pool, etc.)
    }

    /**
     * Clear opcode cache for changed files
     */
    protected function clearOpcacheIfNeeded(): void
    {
        if (function_exists('opcache_reset')) {
            // In development, you might want to clear opcache
            // In production, let RoadRunner's reload feature handle this
        }
    }

    /**
     * Log worker statistics
     */
    protected function logWorkerStats(): void
    {
        $stats = $this->getWorkerStats();
        error_log(sprintf(
            '[RoadRunner Worker] Shutting down. Stats: %s',
            json_encode($stats)
        ));
    }

    /**
     * Shutdown services gracefully
     */
    protected function shutdownServices(): void
    {
        // Close persistent connections
        // Flush any pending data
        // Save worker metrics
    }

    /**
     * Get average request time
     */
    protected function getAverageRequestTime(): float
    {
        if (empty($this->requestMetrics)) {
            return 0.0;
        }

        $total = array_sum(array_column($this->requestMetrics, 'duration'));
        return $total / count($this->requestMetrics);
    }

    /**
     * Parse memory limit string to bytes
     */
    protected function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
            // fall through
            case 'm':
                $value *= 1024;
            // fall through
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
