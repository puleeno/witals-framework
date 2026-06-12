<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts;

/**
 * Resettable Interface
 * Services that hold state and need to be reset between requests in long-running environments.
 */
interface ResettableInterface
{
    /**
     * Reset the service state.
     */
    public function reset(): void;
}
