<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts\Exceptions;

use Throwable;
use Witals\Framework\Http\Response;

interface ExceptionHandlerInterface
{
    /**
     * Report or log an exception.
     *
     * @param Throwable $e
     * @return void
     */
    public function report(Throwable $e): void;

    /**
     * Render an exception into an HTTP response.
     *
     * @param Throwable $e
     * @return Response
     */
    public function render(Throwable $e): Response;
}
