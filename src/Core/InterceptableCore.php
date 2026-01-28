<?php

declare(strict_types=1);

namespace Witals\Framework\Core;

use Witals\Framework\Contracts\Core\CoreInterface;
use Witals\Framework\Contracts\Interceptor\InterceptorInterface;

/**
 * InterceptableCore manages a chain of interceptors wrapping a base core.
 */
class InterceptableCore implements CoreInterface
{
    /** @var InterceptorInterface[] */
    protected array $interceptors = [];

    protected CoreInterface $baseCore;

    public function __construct(CoreInterface $baseCore)
    {
        $this->baseCore = $baseCore;
    }

    /**
     * Add an interceptor to the end of the chain.
     */
    public function addInterceptor(InterceptorInterface $interceptor): void
    {
        $this->interceptors[] = $interceptor;
    }

    /**
     * Set the entire interceptor chain.
     */
    public function setInterceptors(array $interceptors): void
    {
        $this->interceptors = $interceptors;
    }

    /**
     * Call the action through the interceptor chain.
     */
    public function call(string $action, array $parameters = []): mixed
    {
        return $this->getChain($this->interceptors)->call($action, $parameters);
    }

    /**
     * Build the recursive chain of interceptors.
     */
    protected function getChain(array $interceptors): CoreInterface
    {
        if (empty($interceptors)) {
            return $this->baseCore;
        }

        $interceptor = array_shift($interceptors);
        $next = $this->getChain($interceptors);

        return new class($interceptor, $next) implements CoreInterface {
            private InterceptorInterface $interceptor;
            private CoreInterface $next;

            public function __construct(InterceptorInterface $interceptor, CoreInterface $next)
            {
                $this->interceptor = $interceptor;
                $this->next = $next;
            }

            public function call(string $action, array $parameters = []): mixed
            {
                return $this->interceptor->intercept($action, $parameters, $this->next);
            }
        };
    }
}
