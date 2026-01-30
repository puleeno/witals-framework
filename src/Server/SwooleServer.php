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
            'worker_num' => (int)env('SWOOLE_WORKERS', 4),
            'task_worker_num' => (int)env('SWOOLE_TASK_WORKERS', 2),
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

        // Bind server to container for shared access
        $this->app->instance('swoole.server', $server);

        // Worker start event - boot application once per worker
        $server->on('workerStart', function (SwooleHttpServer $server, int $workerId) {
            echo "Worker #{$workerId} started\n";
            $this->app->boot();
        });

        // Request event - handle each request
        $server->on('request', function (SwooleRequest $swooleRequest, SwooleResponse $swooleResponse) {
            $this->handleRequest($swooleRequest, $swooleResponse);
        });

        // Task event - handle async tasks (Actions)
        $server->on('task', function (SwooleHttpServer $server, int $taskId, int $srcWorkerId, $data) {
            if (isset($data['type']) && $data['type'] === 'hook_action') {
                $this->app->make(\Witals\Framework\Hooks\HookManager::class)
                    ->executeDispatchedAction($data['hook_data'], $data['args']);
            }
            return "Task {$taskId} finished";
        });

        $server->on('finish', function (SwooleHttpServer $server, int $taskId, $data) {
            // Task finished
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
        $request = Request::createFromSwoole($swooleRequest);

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
     * @inheritDoc
     */
    public function isStateful(): bool
    {
        return true;
    }
}
