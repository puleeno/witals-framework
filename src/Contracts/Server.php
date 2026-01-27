<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts;

/**
 * Server Interface
 * Defines the contract for all server adapters (ReactPHP, Swoole, RoadRunner, etc.)
 */
interface Server
{
    /**
     * Start the server
     */
    public function start(): void;

    /**
     * Check if the server is stateful (long-running)
     */
    public function isStateful(): bool;
}
