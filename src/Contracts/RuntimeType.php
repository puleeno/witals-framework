<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts;

/**
 * Runtime Type Enumeration
 * Defines supported runtime environments
 */
enum RuntimeType: string
{
    case TRADITIONAL = 'traditional';
    case ROADRUNNER = 'roadrunner';
    case REACTPHP = 'reactphp';
    case SWOOLE = 'swoole';
    case OPENSWOOLE = 'openswoole';

    /**
     * Check if runtime is long-running
     */
    public function isLongRunning(): bool
    {
        return match ($this) {
            self::TRADITIONAL => false,
            self::ROADRUNNER, self::REACTPHP, self::SWOOLE, self::OPENSWOOLE => true,
        };
    }

    /**
     * Check if runtime is async-capable
     */
    public function isAsync(): bool
    {
        return match ($this) {
            self::TRADITIONAL, self::ROADRUNNER => false,
            self::REACTPHP, self::SWOOLE, self::OPENSWOOLE => true,
        };
    }

    /**
     * Detect runtime from environment
     */
    public static function detect(): self
    {
        // Check for RoadRunner (Prioritized)
        if (isset($_SERVER['RR_MODE']) || getenv('RR_MODE')) {
            return self::ROADRUNNER;
        }

        // Check for ReactPHP (via environment variable)
        if (getenv('REACTPHP_MODE') === 'true' || isset($_SERVER['REACTPHP_MODE'])) {
            return self::REACTPHP;
        }

        // Check for OpenSwoole
        if (extension_loaded('openswoole')) {
            return self::OPENSWOOLE;
        }

        // Check for Swoole
        if (extension_loaded('swoole')) {
            return self::SWOOLE;
        }

        return self::TRADITIONAL;
    }
}
