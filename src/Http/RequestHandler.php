<?php

declare(strict_types=1);

namespace Witals\Framework\Http;

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Contracts\Http\Kernel as KernelContract;

/**
 * Request Handler
 * Manages the lifecycle of a single request: init, middleware, execute, response, shutdown
 */
class RequestHandler
{
    protected Application $app;
    protected KernelContract $kernel;

    public function __construct(Application $app, KernelContract $kernel)
    {
        $this->app = $app;
        $this->kernel = $kernel;
    }

    /**
     * Complete request lifecycle
     */
    public function handle(Request $request): Response
    {
        try {
            // 1. Init (Request Start)
            $this->init($request);

            // 2. Middlewares & 3. Execute (Handled by Kernel)
            $response = $this->execute($request);

            // 4. Response (Lifecycle hook before returning)
            $this->respond($request, $response);

            return $response;
        } catch (\Throwable $e) {
            return $this->handleException($e, $request);
        } finally {
            // 5. Shutdown
            $this->shutdown($request, $response ?? null);
        }
    }

    /**
     * Phase 1: Injection & Initialization
     */
    protected function init(Request $request): void
    {
        // 1. Initialize Request ID for logging (Enterprise requirement)
        if ($this->app->has(\Psr\Log\LoggerInterface::class)) {
            $logger = $this->app->make(\Psr\Log\LoggerInterface::class);
            if (method_exists($logger, 'setRequestId')) {
                $logger->setRequestId(bin2hex(random_bytes(8)));
            }
        }

        // 2. Lifecycle: Request Start
        $this->app->lifecycle()->onRequestStart($request);
    }

    /**
     * Phase 2 & 3: Middleware and Execution
     */
    protected function execute(Request $request): Response
    {
        return $this->kernel->handle($request);
    }

    /**
     * Phase 4: Prepare Response
     */
    protected function respond(Request $request, Response $response): void
    {
        // Logic before sending response back to the server adapter
    }

    /**
     * Phase 5: Shutdown & Cleanup
     */
    protected function shutdown(Request $request, ?Response $response): void
    {
        if ($response) {
            // Lifecycle: Request End
            $this->app->lifecycle()->onRequestEnd($request, $response);
        }

        // Flush logs if logger is registered and supports flushing
        if ($this->app->has(\Psr\Log\LoggerInterface::class)) {
            $logger = $this->app->make(\Psr\Log\LoggerInterface::class);
            if (method_exists($logger, 'flush')) {
                $logger->flush();
            }
        }

        // Cleanup after request (instance clearing, etc.)
        $this->app->afterRequest($request, $response ?? new Response('', 500));
    }

    /**
     * Handle exceptions during the request lifecycle
     */
    protected function handleException(\Throwable $e, Request $request): Response
    {
        $handler = $this->app->make(\Witals\Framework\Contracts\Exceptions\ExceptionHandlerInterface::class);
        $handler->report($e);
        return $handler->render($e, $request);
    }
}
