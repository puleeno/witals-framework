<?php

declare(strict_types=1);

namespace Witals\Framework\Support;

use Witals\Framework\Application;

/**
 * Environment Detector
 * Automatically detects the server environment and available capabilities.
 */
class EnvironmentDetector
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Check if running in a modern long-running environment (Swoole/OpenSwoole)
     */
    public function isModern(): bool
    {
        // Only consider Swoole/OpenSwoole runtimes as "Modern" for Shared Memory Table support.
        // RoadRunner, while long-running, doesn't support Swoole Tables across workers.
        return ($this->app->isSwoole() || $this->app->isOpenSwoole()) && 
               (extension_loaded('swoole') || extension_loaded('openswoole'));
    }

    /**
     * Check if APCu is available for Shared Memory on traditional servers
     */
    public function hasAPCu(): bool
    {
        return extension_loaded('apcu') && apcu_enabled();
    }

    /**
     * Check if running on a restricted environment (Shared Hosting)
     */
    public function isRestricted(): bool
    {
        return !$this->isModern() && !$this->hasAPCu();
    }

    /**
     * Get the best available registry type
     */
    public function getBestRegistryType(): string
    {
        if ($this->isModern()) return 'swoole';
        if ($this->hasAPCu()) return 'apcu';
        return 'file'; // Fallback to compiled PHP file
    }
}
