<?php

declare(strict_types=1);

namespace Witals\Framework\Server;

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Contracts\RuntimeType;
use React\Http\HttpServer;
use React\Socket\SocketServer;
use React\EventLoop\Loop;
use Psr\Http\Message\ServerRequestInterface;

use Witals\Framework\Contracts\Server;

/**
 * ReactPHP Server Adapter
 * Runs the application using ReactPHP event loop
 */
class ReactPhpServer implements Server
{
    protected Application $app;
    protected string $host;
    protected int $port;

    public function __construct(Application $app, string $host = '0.0.0.0', int $port = 8080)
    {
        $this->app = $app;
        $this->host = $host;
        $this->port = $port;

        // Set runtime mode
        $this->app->setRuntime(RuntimeType::REACTPHP);
    }

    /**
     * Start the ReactPHP server
     */
    public function start(): void
    {
        $this->app->boot();

        $server = new HttpServer(function (ServerRequestInterface $psrRequest) {
            try {
                return $this->handleRequest($psrRequest);
            } catch (\Throwable $e) {
                file_put_contents('php://stderr', sprintf(
                    "[ReactPHP Error] %s in %s:%d\n%s\n",
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString()
                ));

                return new \React\Http\Message\Response(
                    500,
                    ['Content-Type' => 'text/plain'],
                    'Internal Server Error'
                );
            }
        });

        $socket = new SocketServer("{$this->host}:{$this->port}");
        $server->listen($socket);

        echo "ReactPHP server running on http://{$this->host}:{$this->port}\n";
        echo "Press Ctrl+C to stop\n";
    }

    /**
     * Handle incoming PSR-7 request
     */
    protected function handleRequest(ServerRequestInterface $psrRequest): \React\Http\Message\Response
    {
        // Convert PSR-7 request to Witals Request using the built-in factory
        $request = Request::createFromPsr7($psrRequest);

        // Handle request through application
        $response = $this->app->handle($request);

        // Clean up after request
        $this->app->afterRequest($request, $response);

        // Convert Witals Response to PSR-7 Response
        return new \React\Http\Message\Response(
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getContent()
        );
    }

    /**
     * @inheritDoc
     */
    public function isStateful(): bool
    {
        return true;
    }
}
