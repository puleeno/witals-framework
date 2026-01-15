<?php

declare(strict_types=1);

namespace Witals\Framework\Lifecycle;

use Witals\Framework\Contracts\LifecycleManager;
use Witals\Framework\Application;

/**
 * Lifecycle Manager Factory
 * Creates appropriate lifecycle manager based on server adapter
 */
class LifecycleFactory
{
    /**
     * Create lifecycle manager based on application environment
     */
    public static function create(Application $app): LifecycleManager
    {
        if ($app->isRoadRunner()) {
            return $app->make(RoadRunnerLifecycle::class);
        }

        return $app->make(TraditionalLifecycle::class);
    }

    /**
     * Create traditional lifecycle explicitly
     * Note: This strictly creates a new instance, usage of create() via Application is preferred.
     */
    public static function createTraditional(): LifecycleManager
    {
        return new TraditionalLifecycle();
    }

    /**
     * Create RoadRunner lifecycle explicitly
     * Note: This strictly creates a new instance, usage of create() via Application is preferred.
     */
    public static function createRoadRunner(): LifecycleManager
    {
        return new RoadRunnerLifecycle();
    }
}
