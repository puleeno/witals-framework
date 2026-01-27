<?php

declare(strict_types=1);

namespace Witals\Framework\Server;

use Witals\Framework\Application;
use Witals\Framework\Contracts\Server;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Http\Request;
use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Http\PSR7Worker;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * RoadRunner Worker Adapter
 * Handles requests passed from the RoadRunner server binary
 */
class RoadRunnerServer implements Server
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;

        // Set runtime mode
        $this->app->setRuntime(RuntimeType::ROADRUNNER);
    }

    /**
     * Start the RoadRunner worker loop
     */
    public function start(): void
    {
        if (!class_exists(Worker::class)) {
            echo "RoadRunner is not installed. Run: composer require spiral/roadrunner-http\n";
            exit(1);
        }

        file_put_contents('php://stderr', "Starting RoadRunner worker...\n");
        file_put_contents('php://stderr', "Note: RoadRunner manages the HTTP server. This worker handles requests.\n");
        file_put_contents('php://stderr', "Start RoadRunner with: ./rr serve\n\n");

        // Boot the application
        $this->app->boot();

        // Create RoadRunner worker
        $worker = Worker::create();
        $factory = new Psr17Factory();
        $psr7Worker = new PSR7Worker($worker, $factory, $factory, $factory);

        // Worker loop - handles multiple requests
        while ($req = $psr7Worker->waitRequest()) {
            try {
                // Convert PSR-7 request to application request
                $request = Request::createFromPsr7($req);

                // Handle the request
                $response = $this->app->handle($request);

                // Convert application response to PSR-7 response
                $psr7Response = $response->toPsr7($factory);

                // Send the response back to RoadRunner
                $psr7Worker->respond($psr7Response);

                // Clean up after request
                $this->app->afterRequest($request, $response);

            } catch (\Throwable $e) {
                $this->handleError($e, $psr7Worker, $factory);
            }
        }
    }

    /**
     * Handle RoadRunner worker errors
     */
    protected function handleError(\Throwable $e, PSR7Worker $psr7Worker, Psr17Factory $factory): void
    {
        error_log(sprintf(
            "[RoadRunner Error] %s in %s:%d\n%s",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ));

        try {
            $errorResponse = $factory->createResponse(500)
                ->withHeader('Content-Type', 'application/json');
            
            $errorResponse->getBody()->write(json_encode([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ]));

            $psr7Worker->respond($errorResponse);
        } catch (\Throwable $inner) {
            // If even responding fails, let it die
            $psr7Worker->getWorker()->error((string)$e);
        }
    }

    /**
     * @inheritDoc
     */
    public function isStateful(): bool
    {
        return true;
    }
}
