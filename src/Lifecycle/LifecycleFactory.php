<?php

declare(strict_types=1);

namespace Witals\Framework\Lifecycle;

use Witals\Framework\Contracts\LifecycleManager;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Application;

class LifecycleFactory
{
    private const RUNTIME_MAP = [
        'roadrunner' => RoadRunnerLifecycle::class,
        'frankenphp' => FrankenPhpLifecycle::class,
        'reactphp' => ReactPhpLifecycle::class,
        'swoole' => SwooleLifecycle::class,
        'openswoole' => OpenSwooleLifecycle::class,
        'traditional' => TraditionalLifecycle::class,
    ];

    public static function create(Application $app): LifecycleManager
    {
        $runtime = $app->getRuntime();

        return $app->make(self::resolveClass($runtime));
    }

    public static function createByRuntime(RuntimeType $runtime): LifecycleManager
    {
        $class = self::resolveClass($runtime);

        return new $class();
    }

    private static function resolveClass(RuntimeType $runtime): string
    {
        return self::RUNTIME_MAP[$runtime->value] ?? TraditionalLifecycle::class;
    }
}
