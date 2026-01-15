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
            return new RoadRunnerLifecycle();
        }

        return new TraditionalLifecycle();
    }

    /**
     * Create traditional lifecycle explicitly
     */
    public static function createTraditional(): LifecycleManager
    {
        return new TraditionalLifecycle();
    }

    /**
     * Create RoadRunner lifecycle explicitly
     */
    public static function createRoadRunner(): LifecycleManager
    {
        return new RoadRunnerLifecycle();
    }
}
