<?php

declare(strict_types=1);

namespace Witals\Framework\Log\Drivers;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Debug Logger
 * Highly visible log output for development environments (CLI/STDERR)
 */
class DebugLogger extends AbstractLogger
{
    protected int $minLevel = 100;

    protected static array $levels = [
        'debug'     => 100,
        'info'      => 200,
        'notice'    => 250,
        'warning'   => 300,
        'error'     => 400,
        'critical'  => 500,
        'alert'     => 550,
        'emergency' => 600,
    ];

    public function __construct(string|int $minLevel = 'debug')
    {
        $this->minLevel = is_int($minLevel) ? $minLevel : (self::$levels[strtolower($minLevel)] ?? 100);
    }

    /**
     * Log to stderr for immediate visibility in CLI workers
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $levelValue = self::$levels[strtolower((string)$level)] ?? 100;

        if ($levelValue < $this->minLevel) {
            return;
        }

        $colors = [
            'emergency' => "\033[41;37m", // Red background
            'alert'     => "\033[41;37m",
            'critical'  => "\033[41;37m",
            'error'     => "\033[31m",    // Red text
            'warning'   => "\033[33m",    // Yellow
            'notice'    => "\033[34m",    // Blue
            'info'      => "\033[32m",    // Green
            'debug'     => "\033[37m",    // Gray
        ];

        $reset = "\033[0m";
        $color = $colors[(string)$level] ?? $reset;

        $timestamp = date('H:i:s');
        $output = sprintf(
            "%s[%s] %s%s: %s%s\n",
            $color,
            $timestamp,
            strtoupper((string)$level),
            $reset,
            $message,
            !empty($context) ? ' ' . json_encode($context) : ''
        );

        file_put_contents('php://stderr', $output);
    }
}
