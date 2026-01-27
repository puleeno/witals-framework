<?php

declare(strict_types=1);

namespace Witals\Framework\Server;

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Contracts\RuntimeType;
use Swoole\Http\Server as SwooleHttpServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

use Witals\Framework\Contracts\Server;

/**
 * Swoole Server Adapter
 * Runs the application using Swoole HTTP server
 */
class SwooleServer implements Server
{
    protected Application $app;
    protected string $host;
    protected int $port;
    protected array $options;

    public function __construct(
        Application $app,
        string $host = '0.0.0.0',
        int $port = 8080,
        array $options = []
    ) {
        $this->app = $app;
        $this->host = $host;
        $this->port = $port;
        $this->options = array_merge([
            'worker_num' => swoole_cpu_num() * 2,
            'enable_coroutine' => true,
            'max_coroutine' => 100000,
        ], $options);

        // Set runtime mode
        $this->app->setRuntime(RuntimeType::SWOOLE);
    }

    /**
     * Start the Swoole server
     */
    public function start(): void
    {
        $server = new SwooleHttpServer($this->host, $this->port);
        $server->set($this->options);

        // Worker start event - boot application once per worker
        $server->on('workerStart', function (SwooleHttpServer $server, int $workerId) {
            echo "Worker #{$workerId} started\n";
            $this->app->boot();
        });

        // Request event - handle each request
        $server->on('request', function (SwooleRequest $swooleRequest, SwooleResponse $swooleResponse) {
            $this->handleRequest($swooleRequest, $swooleResponse);
        });

        echo "Swoole HTTP server running on http://{$this->host}:{$this->port}\n";
        echo "Workers: {$this->options['worker_num']}\n";
        echo "Press Ctrl+C to stop\n";

        $server->start();
    }

    /**
     * Handle incoming Swoole request
     */
    protected function handleRequest(SwooleRequest $swooleRequest, SwooleResponse $swooleResponse): void
    {
        // Convert Swoole request to Witals Request
        $request = $this->convertSwooleRequest($swooleRequest);

        // Handle request through application
        $response = $this->app->handle($request);

        // Clean up after request
        $this->app->afterRequest($request, $response);

        // Send response
        $swooleResponse->status($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            foreach ((array) $values as $value) {
                $swooleResponse->header($name, $value);
            }
        }

        $swooleResponse->end($response->getContent());
    }

    /**
     * Convert Swoole request to Witals Request
     */
    protected function convertSwooleRequest(SwooleRequest $swooleRequest): Request
    {
        $get = $swooleRequest->get ?? [];
        $post = $swooleRequest->post ?? [];
        $cookie = $swooleRequest->cookie ?? [];
        $files = $swooleRequest->files ?? [];
        $server = $swooleRequest->server ?? [];
        $headers = $swooleRequest->header ?? [];

        // Merge headers into server array
        foreach ($headers as $name => $value) {
            $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $server[$key] = $value;
        }

        // Get raw body
        $content = $swooleRequest->rawContent() ?? '';

        return new Request($get, $post, [], $cookie, $files, $server, $content);
    }

    /**
     * @inheritDoc
     */
    public function isStateful(): bool
    {
        return true;
    }
}
