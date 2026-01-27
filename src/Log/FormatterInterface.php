<?php

declare(strict_types=1);

namespace Witals\Framework\Log;

/**
 * Log Formatter Interface
 */
interface FormatterInterface
{
    /**
     * Format a log record
     */
    public function format(string $level, string $message, array $context): string;
}
