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
        
        $interpolatedKeys = [];
        $message = $this->interpolate($message, $context, $interpolatedKeys);
        
        // Remove interpolated items from context string
        $remainingContext = array_diff_key($context, array_flip($interpolatedKeys));
        
        $contextString = !empty($remainingContext) ? ' ' . json_encode($remainingContext, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';

        return sprintf("[%s] %s: %s%s\n", $timestamp, strtoupper($level), $message, $contextString);
    }

    /**
     * Interpolates context values into the message placeholders.
     */
    protected function interpolate(string $message, array $context, array &$interpolatedKeys = []): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            $placeholder = '{' . $key . '}';
            if (str_contains($message, $placeholder)) {
                if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                    $replace[$placeholder] = (string)$val;
                    $interpolatedKeys[] = $key;
                }
            }
        }

        return strtr($message, $replace);
    }
}
