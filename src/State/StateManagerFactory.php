<?php

declare(strict_types=1);

namespace Witals\Framework\State;

use Witals\Framework\Contracts\StateManager;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Application;

/**
 * State Manager Factory
 * Creates appropriate state manager based on server adapter
 */
class StateManagerFactory
{
    /**
     * Create state manager based on application environment
     */
    public static function create(Application $app): StateManager
    {
        $runtime = $app->getRuntime();

        // Long-running runtimes need stateful managers
        if ($runtime->isLongRunning()) {
            return $app->make(StatefulManager::class);
        }

        return $app->make(StatelessManager::class);
    }

    /**
     * Create state manager by runtime type
     */
    public static function createByRuntime(RuntimeType $runtime): StateManager
    {
        if ($runtime->isLongRunning()) {
            return new StatefulManager();
        }

        return new StatelessManager();
    }

    /**
     * Create stateless manager explicitly
     * Note: This strictly creates a new instance, usage of create() via Application is preferred.
     */
    public static function createStateless(): StateManager
    {
        return new StatelessManager();
    }

    /**
     * Create stateful manager explicitly
     * Note: This strictly creates a new instance, usage of create() via Application is preferred.
     */
    public static function createStateful(): StateManager
    {
        return new StatefulManager();
    }
}
