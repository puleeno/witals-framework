<?php

declare(strict_types=1);

namespace Witals\Framework\Log\Formatters;

use Witals\Framework\Log\FormatterInterface;

/**
 * Enterprise Json Formatter
 * Ideal for ELK Stack, Splunk, CloudWatch
 */
class JsonFormatter implements FormatterInterface
{
    public function format(string $level, string $message, array $context): string
    {
        $record = array_merge([
            '@timestamp' => date('c'),
            'level'      => strtoupper($level),
            'message'    => $message,
        ], $context);

        return json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }
}
