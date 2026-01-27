<?php

declare(strict_types=1);

namespace Witals\Framework\Log\Drivers;

use Psr\Log\AbstractLogger;
use Stringable;
use Witals\Framework\Log\FormatterInterface;
use Witals\Framework\Log\Formatters\LineFormatter;

/**
 * Standard Enterprise Logger
 * High-performance buffered logger with support for Formatters and Processors
 */
class StandardLogger extends AbstractLogger
{
    protected string $path;
    protected array $buffer = [];
    protected bool $buffered = true;
    protected FormatterInterface $formatter;
    protected array $processors = [];

    public function __construct(
        string $path, 
        bool $buffered = true, 
        ?FormatterInterface $formatter = null
    ) {
        $this->path = $path;
        $this->buffered = $buffered;
        $this->formatter = $formatter ?: new LineFormatter();
    }

    /**
     * Add a processor to the logger
     */
    public function pushProcessor(callable $processor): self
    {
        $this->processors[] = $processor;
        return $this;
    }

    /**
     * Log a message
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        // 1. Apply Processors
        $record = [
            'level' => (string)$level,
            'message' => (string)$message,
            'context' => $context
        ];

        foreach ($this->processors as $processor) {
            $record = $processor($record);
        }

        // 2. Format the log entry
        $logEntry = $this->formatter->format(
            $record['level'], 
            $record['message'], 
            $record['context'] + array_diff_key($record, ['level' => 1, 'message' => 1, 'context' => 1])
        );

        // 3. Handle Output
        if ($this->buffered) {
            $this->buffer[] = $logEntry;
        } else {
            $this->write($logEntry);
        }
    }

    /**
     * Write to the log file (Atomic write)
     */
    protected function write(string $content): void
    {
        if (empty($content)) return;
        
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        
        file_put_contents($this->path, $content, FILE_APPEND | LOCK_EX);
    }

    /**
     * Flush the buffer
     */
    public function flush(): void
    {
        if (empty($this->buffer)) {
            return;
        }

        $this->write(implode('', $this->buffer));
        $this->buffer = [];
    }

    public function __destruct()
    {
        $this->flush();
    }
}
