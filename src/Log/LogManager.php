<?php

declare(strict_types=1);

namespace Witals\Framework\Log;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use Witals\Framework\Log\Formatters\JsonFormatter;
use Witals\Framework\Log\Formatters\LineFormatter;
use Witals\Framework\Log\Processors\RequestIdProcessor;

/**
 * Log Manager (Enterprise Model)
 * Orchestrates logging channels, drivers, formatters, and processors.
 */
class LogManager extends AbstractLogger
{
    protected array $handlers = [];
    protected string $default = 'standard';
    protected array $config = [];
    protected array $globalProcessors = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->default = $config['default'] ?? 'standard';
        
        // Add RequestId by default in enterprise model
        $this->globalProcessors['request_id'] = new RequestIdProcessor();
    }

    /**
     * Set the request ID for the current lifecycle
     */
    public function setRequestId(string $id): void
    {
        if (isset($this->globalProcessors['request_id'])) {
            $this->globalProcessors['request_id']->setRequestId($id);
        }
    }

    /**
     * Get a channel/driver instance
     */
    public function driver(?string $name = null): LoggerInterface
    {
        $name = $name ?: $this->default;

        if (!isset($this->handlers[$name])) {
            $this->handlers[$name] = $this->createDriver($name);
        }

        return $this->handlers[$name];
    }

    /**
     * Create a new driver instance based on config
     */
    protected function createDriver(string $name): LoggerInterface
    {
        $config = $this->config['channels'][$name] ?? ['driver' => $name];
        $driverName = $config['driver'] ?? $name;
        
        $method = 'create' . ucfirst($driverName) . 'Driver';

        if (method_exists($this, $method)) {
            $driver = $this->$method($config);
            
            // Push global processors to drivers that support them
            if (method_exists($driver, 'pushProcessor')) {
                foreach ($this->globalProcessors as $processor) {
                    $driver->pushProcessor($processor);
                }
            }
            
            return $driver;
        }

        throw new InvalidArgumentException("Driver [{$driverName}] not supported.");
    }

    /**
     * Create standard file driver
     */
    protected function createStandardDriver(array $config): LoggerInterface
    {
        $path = $config['path'] ?? '/home/puleeno/Projects/witals.com/storage/logs/witals.log';
        $buffered = $config['buffered'] ?? true;
        $level = $config['level'] ?? 'debug';
        
        $formatter = $this->resolveFormatter($config['formatter'] ?? 'line');

        // Ensure directory
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return new Drivers\StandardLogger($path, $buffered, $formatter, $level);
    }

    /**
     * Create debug driver for CLI/SAPI
     */
    protected function createDebugDriver(array $config): LoggerInterface
    {
        $level = $config['level'] ?? 'debug';
        return new Drivers\DebugLogger($level);
    }

    /**
     * Create null driver
     */
    protected function createNullDriver(array $config): LoggerInterface
    {
        return new Drivers\NullLogger();
    }

    /**
     * Resolve formatter by name
     */
    protected function resolveFormatter(string $name): FormatterInterface
    {
        return match ($name) {
            'json' => new JsonFormatter(),
            default => new LineFormatter(),
        };
    }

    /**
     * Flush all buffered handlers (Critical for performance)
     */
    public function flush(): void
    {
        foreach ($this->handlers as $handler) {
            if (method_exists($handler, 'flush')) {
                $handler->flush();
            }
        }
    }

    /**
     * Proxied log method
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->driver()->log($level, $message, $context);
    }

    public function __destruct()
    {
        $this->flush();
    }

    /**
     * Dynamically call the default driver instance.
     */
    public function __call(string $method, array $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }
}
