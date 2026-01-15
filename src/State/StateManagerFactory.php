<?php

declare(strict_types=1);

namespace Witals\Framework\State;

use Witals\Framework\Contracts\StateManager;
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
        if ($app->isRoadRunner()) {
            return $app->make(StatefulManager::class);
        }

        return $app->make(StatelessManager::class);
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
