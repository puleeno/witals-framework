<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts\Http;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * HTTP Kernel Contract
 */
interface Kernel
{
    /**
     * Handle an incoming HTTP request
     */
    public function handle(Request $request): Response;
}
