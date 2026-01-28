<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts\Core;

/**
 * Core interface defines the dispatching mechanism for framework actions.
 * This can be wrapped by interceptors.
 */
interface CoreInterface
{
    /**
     * Dispatch an action to a target.
     *
     * @param string $action Action name or class::method
     * @param array $parameters Arguments for the action
     * @return mixed
     */
    public function call(string $action, array $parameters = []): mixed;
}
