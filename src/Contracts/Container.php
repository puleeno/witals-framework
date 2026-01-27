<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts;

interface Container
{
    /**
     * Register a binding with the container.
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void;

    /**
     * Register a shared binding in the container.
     */
    public function singleton(string $abstract, $concrete = null): void;

    /**
     * Register an existing instance as shared in the container.
     */
    public function instance(string $abstract, mixed $instance): void;

    /**
     * Resolve the given type from the container.
     *
     * @return mixed
     */
    public function make(string $abstract, array $parameters = []);

    /**
     * Run a closure within a specific scope.
     *
     * @return mixed
     */
    public function runScope(array $bindings, callable $callback);

    /**
     * Remove a resolved instance from the instance cache.
     */
    public function forgetInstance(string $abstract): void;

    /**
     * Flush the container of all bindings and resolved instances.
     */
    public function flush(): void;

    /**
     * Get the container's bindings.
     */
    public function getBindings(): array;

    /**
     * Determine if the given abstract type has been bound.
     */
    public function has(string $abstract): bool;
}
