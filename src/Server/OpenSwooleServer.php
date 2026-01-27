<?php

declare(strict_types=1);

namespace Witals\Framework\Server;

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Contracts\RuntimeType;
use OpenSwoole\Http\Server as OpenSwooleHttpServer;
use OpenSwoole\Http\Request as OpenSwooleRequest;
use OpenSwoole\Http\Response as OpenSwooleResponse;

use Witals\Framework\Contracts\Server;

/**
 * OpenSwoole Server Adapter
 * Runs the application using OpenSwoole HTTP server
 */
class OpenSwooleServer implements Server
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
        
        // Get CPU count - OpenSwoole uses different function
        $cpuNum = function_exists('swoole_cpu_num') 
            ? swoole_cpu_num() 
            : (function_exists('openswoole_cpu_num') ? openswoole_cpu_num() : 4);
            
        $this->options = array_merge([
            'worker_num' => $cpuNum * 2,
            'enable_coroutine' => true,
            'max_coroutine' => 100000,
        ], $options);

        // Set runtime mode
        $this->app->setRuntime(RuntimeType::OPENSWOOLE);
    }

    /**
     * Start the OpenSwoole server
     */
    public function start(): void
    {
        $server = new OpenSwooleHttpServer($this->host, $this->port);
        $server->set($this->options);

        // Worker start event - boot application once per worker
        $server->on('workerStart', function (OpenSwooleHttpServer $server, int $workerId) {
            echo "Worker #{$workerId} started\n";
            $this->app->boot();
        });

        // Request event - handle each request
        $server->on('request', function (OpenSwooleRequest $openswooleRequest, OpenSwooleResponse $openswooleResponse) {
            $this->handleRequest($openswooleRequest, $openswooleResponse);
        });

        echo "OpenSwoole HTTP server running on http://{$this->host}:{$this->port}\n";
        echo "Workers: {$this->options['worker_num']}\n";
        echo "Press Ctrl+C to stop\n";

        $server->start();
    }

    /**
     * Handle incoming OpenSwoole request
     */
    protected function handleRequest(OpenSwooleRequest $openswooleRequest, OpenSwooleResponse $openswooleResponse): void
    {
        // Convert OpenSwoole request to Witals Request
        $request = Request::createFromSwoole($openswooleRequest);

        // Handle request through application
        $response = $this->app->handle($request);

        // Clean up after request
        $this->app->afterRequest($request, $response);

        // Send response
        $openswooleResponse->status($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            foreach ((array) $values as $value) {
                $openswooleResponse->header($name, $value);
            }
        }

        $openswooleResponse->end($response->getContent());
    }

    /**
     * @inheritDoc
     */
    public function isStateful(): bool
    {
        return true;
    }
}
