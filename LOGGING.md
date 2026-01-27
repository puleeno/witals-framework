# Enterprise Logging System

Witals Framework provides a high-performance, enterprise-ready logging system based on the PSR-3 standard. It is designed to be extremely fast in long-running environments (RoadRunner, Swoole, ReactPHP) by using memory buffering.

## Features

- **PSR-3 Compliant**: Use it with any PSR-3 compatible library.
- **High Performance**: Memory buffering ensures that logs are written to disk in a single atomic operation at the end of the request.
- **Enterprise Ready**: Support for Formatters (JSON, Line) and Processors.
- **Traceability**: Automatic `request_id` (Correlation ID) injection into every log entry.
- **Multiple Drivers**:
    - `StandardLogger`: High-performance buffered file logger.
    - `DebugLogger`: Colorized CLI output for development.
    - `NullLogger`: Silently discards logs.

## Configuration

Logging is configured in `bootstrap/app.php`:

```php
$app->singleton(\Psr\Log\LoggerInterface::class, function ($app) {
    return new \Witals\Framework\Log\LogManager([
        'default'  => getenv('APP_DEBUG') === 'true' ? 'debug' : 'standard',
        'channels' => [
            'standard' => [
                'driver'    => 'standard',
                'path'      => $app->basePath('storage/logs/witals.log'),
                'buffered'  => true,
                'formatter' => 'json', // JSON is ideal for ELK Stack / Splunk
            ],
            'debug' => [
                'driver' => 'debug',
            ],
        ],
    ]);
});
```

## Performance Optimization: Memory Buffering

In a traditional PHP-FPM environment, every `log()` call might perform a disk I/O operation. In high-performance runtimes, this is a bottleneck.

Witals solves this by:
1.  Collecting logs in a memory buffer.
2.  Using `RequestHandler` to automatically `flush()` the buffer at the end of the request lifecycle.
3.  Writing to disk using `LOCK_EX` to ensure data integrity during concurrent writes.

## Traceability with Request ID

Every log entry automatically includes a `request_id`. This allows you to correlate all logs belonging to a single user request across multiple services or logs.

Example JSON output:
```json
{"@timestamp":"2026-01-27T14:17:52+00:00","level":"INFO","message":"Incoming request: GET /health","request_id":"0a7ddac176bfdaca"}
```

## Using the Logger

You can type-hint `Psr\Log\LoggerInterface` in your constructors:

```php
use Psr\Log\LoggerInterface;

public function __construct(LoggerInterface $logger)
{
    $this->logger = $logger;
}

public function handle()
{
    $this->logger->info("User {id} performed an action", ['id' => 123]);
}
```
