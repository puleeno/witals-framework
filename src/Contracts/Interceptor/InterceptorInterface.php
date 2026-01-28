<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts\Interceptor;

use Witals\Framework\Contracts\Core\CoreInterface;

/**
 * Interceptor allows wrapping calls to the Core with additional logic.
 */
interface InterceptorInterface
{
    /**
     * Intercept the call and return the result.
     *
     * @param string $action The action being called
     * @param array $parameters Parameters passed to the call
     * @param CoreInterface $core The next core in the chain
     * @return mixed
     */
    public function intercept(string $action, array $parameters, CoreInterface $core): mixed;
}
