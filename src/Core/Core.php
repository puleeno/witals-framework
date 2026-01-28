<?php

declare(strict_types=1);

namespace Witals\Framework\Core;

use Witals\Framework\Contracts\Core\CoreInterface;
use Witals\Framework\Contracts\Container;
use InvalidArgumentException;

/**
 * Base Core implementation that resolves and calls actions from the container.
 */
class Core implements CoreInterface
{
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Call an action. Supporting class@method or closure.
     */
    public function call(string $action, array $parameters = []): mixed
    {
        if (str_contains($action, '@')) {
            [$class, $method] = explode('@', $action);
            $instance = $this->container->make($class);
            return $this->container->call([$instance, $method], $parameters);
        }

        if (str_contains($action, '::')) {
            [$class, $method] = explode('::', $action);
            $instance = $this->container->make($class);
            return $this->container->call([$instance, $method], $parameters);
        }

        // If it's a class with __invoke
        if (class_exists($action)) {
            $instance = $this->container->make($action);
            return $this->container->call($instance, $parameters);
        }

        throw new InvalidArgumentException("Unable to resolve core action: {$action}");
    }
}
