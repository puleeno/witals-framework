<?php

declare(strict_types=1);

namespace Witals\Framework\Lifecycle;

use Witals\Framework\Contracts\LifecycleManager;
use Witals\Framework\Contracts\RuntimeType;
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
        $runtime = $app->getRuntime();

        return match ($runtime) {
            RuntimeType::ROADRUNNER => $app->make(RoadRunnerLifecycle::class),
            RuntimeType::REACTPHP => $app->make(ReactPhpLifecycle::class),
            RuntimeType::SWOOLE => $app->make(SwooleLifecycle::class),
            RuntimeType::OPENSWOOLE => $app->make(OpenSwooleLifecycle::class),
            RuntimeType::TRADITIONAL => $app->make(TraditionalLifecycle::class),
        };
    }

    /**
     * Create lifecycle manager by runtime type
     */
    public static function createByRuntime(RuntimeType $runtime): LifecycleManager
    {
        return match ($runtime) {
            RuntimeType::ROADRUNNER => new RoadRunnerLifecycle(),
            RuntimeType::REACTPHP => new ReactPhpLifecycle(),
            RuntimeType::SWOOLE => new SwooleLifecycle(),
            RuntimeType::OPENSWOOLE => new OpenSwooleLifecycle(),
            RuntimeType::TRADITIONAL => new TraditionalLifecycle(),
        };
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

    /**
     * Create ReactPHP lifecycle explicitly
     * Note: This strictly creates a new instance, usage of create() via Application is preferred.
     */
    public static function createReactPhp(): LifecycleManager
    {
        return new ReactPhpLifecycle();
    }

    /**
     * Create Swoole lifecycle explicitly
     * Note: This strictly creates a new instance, usage of create() via Application is preferred.
     */
    public static function createSwoole(): LifecycleManager
    {
        return new SwooleLifecycle();
    }

    /**
     * Create OpenSwoole lifecycle explicitly
     * Note: This strictly creates a new instance, usage of create() via Application is preferred.
     */
    public static function createOpenSwoole(): LifecycleManager
    {
        return new OpenSwooleLifecycle();
    }
}
