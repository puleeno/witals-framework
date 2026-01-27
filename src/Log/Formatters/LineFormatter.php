<?php

declare(strict_types=1);

namespace Witals\Framework\Log\Formatters;

use Witals\Framework\Log\FormatterInterface;

/**
 * Line Formatter
 * Human-readable log format for development
 */
class LineFormatter implements FormatterInterface
{
    public function format(string $level, string $message, array $context): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';

        return sprintf("[%s] %s: %s%s\n", $timestamp, strtoupper($level), $message, $contextString);
    }
}
