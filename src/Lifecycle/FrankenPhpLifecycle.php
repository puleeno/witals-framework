<?php

declare(strict_types=1);

namespace Witals\Framework\Lifecycle;

/**
 * FrankenPHP Lifecycle Manager
 *
 * FrankenPHP runs in worker mode — app boots once, handles many requests.
 * Identical semantics to RoadRunner: long-running PSR-7 worker.
 */
class FrankenPhpLifecycle extends RoadRunnerLifecycle
{
    public function getLifecycleType(): string
    {
        return 'frankenphp';
    }
}
