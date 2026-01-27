<?php

declare(strict_types=1);

namespace Witals\Framework\Log\Drivers;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Null Logger
 * Discards all logs. Used when logging is disabled to avoid if(logger) checks.
 */
class NullLogger extends AbstractLogger
{
    public function log($level, string|Stringable $message, array $context = []): void
    {
        // Do nothing
    }
}
