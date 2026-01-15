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
            return new StatefulManager();
        }

        return new StatelessManager();
    }

    /**
     * Create stateless manager explicitly
     */
    public static function createStateless(): StateManager
    {
        return new StatelessManager();
    }

    /**
     * Create stateful manager explicitly
     */
    public static function createStateful(): StateManager
    {
        return new StatefulManager();
    }
}
