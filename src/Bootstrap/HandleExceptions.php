<?php

declare(strict_types=1);

namespace Witals\Framework\Bootstrap;

use Witals\Framework\Application;
use Witals\Framework\Contracts\Exceptions\ExceptionHandlerInterface;
use Witals\Framework\Exceptions\Handler;
use ErrorException;
use Throwable;

class HandleExceptions
{
    protected Application $app;

    public function bootstrap(Application $app): void
    {
        $this->app = $app;

        error_reporting(-1);

        set_error_handler(function ($level, $message, $file = '', $line = 0) {
            $this->handleError($level, $message, $file, $line);
        });

        set_exception_handler(function (Throwable $e) {
            $this->handleException($e);
        });

        register_shutdown_function(function () {
            $this->handleShutdown();
        });

        ini_set('error_log', $app->getErrorLogPath());

        if (!$app->isLongRunning()) {
            ini_set('display_errors', '0');
        }
    }

    /**
     * Convert PHP errors to ErrorException instances.
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0): void
    {
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Handle an uncaught exception from the application.
     */
    public function handleException(Throwable $e): void
    {
        static $handling = false;

        if ($handling) {
            return;
        }

        $handling = true;

        try {
            $this->app->handleException($e);
        } catch (Throwable $f) {
            // Last resort: log to PHP error log
            error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            error_log('Critical error during exception handling: ' . $f->getMessage());
        } finally {
            $handling = false;
        }
    }

    /**
     * Handle the PHP shutdown event.
     */
    public function handleShutdown(): void
    {
        if (!is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $this->handleException(new ErrorException(
                $error['message'], 0, $error['type'], $error['file'], $error['line']
            ));
        }
    }

    /**
     * Determine if the error type is fatal.
     */
    protected function isFatal(int $type): bool
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }
}
