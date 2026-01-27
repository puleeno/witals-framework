<?php

declare(strict_types=1);

namespace Witals\Framework\Log;

/**
 * Log Processor Interface
 */
interface ProcessorInterface
{
    /**
     * Process a log record to add extra context
     */
    public function __invoke(array $record): array;
}
