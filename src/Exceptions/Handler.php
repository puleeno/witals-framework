<?php

declare(strict_types=1);

namespace Witals\Framework\Exceptions;

use Throwable;
use Psr\Log\LoggerInterface;
use Witals\Framework\Application;
use Witals\Framework\Http\Response;
use Witals\Framework\Contracts\Exceptions\ExceptionHandlerInterface;

class Handler implements ExceptionHandlerInterface
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Report or log an exception.
     */
    public function report(Throwable $e): void
    {
        if ($this->app->has(LoggerInterface::class)) {
            $logger = $this->app->make(LoggerInterface::class);
            $logger->error($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render(Throwable $e): Response
    {
        $debug = false;
        try {
            if ($this->app->has('config')) {
                $debug = $this->app->make('config')->get('app.debug', false);
            }
        } catch (Throwable) {
            // Ignore config errors during fatal rendering
        }

        $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

        $content = [
            'message' => $e->getMessage(),
        ];

        if ($debug) {
            $content['exception'] = get_class($e);
            $content['file'] = $e->getFile();
            $content['line'] = $e->getLine();
            $content['trace'] = $e->getTrace();
        }

        return new Response(
            json_encode($content, JSON_PRETTY_PRINT),
            $status,
            ['Content-Type' => 'application/json']
        );
    }
}
