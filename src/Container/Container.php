<?php

declare(strict_types=1);

namespace Witals\Framework\Container;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

use Witals\Framework\Contracts\Container as ContainerContract;

class Container implements ContainerContract
{
    /**
     * The current globally available container (if any).
     */
    protected static ?Container $instance = null;

    /**
     * The container's bindings.
     */
    protected array $bindings = [];

    /**
     * The container's shared instances.
     */
    protected array $instances = [];

    /**
     * Get the globally available instance of the container.
     */
    public static function getInstance(): ?self
    {
        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     */
    public static function setInstance(?self $container = null): ?self
    {
        return static::$instance = $container;
    }

    /**
     * Register a binding with the container.
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        // If no concrete type was given, we will simply set the concrete type to the
        // abstract type.
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * Register a shared binding in the container.
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        // If the concrete is an actual object (and not a Closure), we treat it as an instance directly
        if (is_object($concrete) && !$concrete instanceof Closure) {
            $this->instance($abstract, $concrete);
            return;
        }

        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as shared in the container.
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve the given type from the container.
     */
    public function make(string $abstract, array $parameters = [])
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * Resolve the given type.
     */
    protected function resolve(string $abstract, array $parameters = [])
    {
        // 1. If we have a shared instance, return it.
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // 2. Get the concrete type or closure.
        $concrete = $this->getConcrete($abstract);

        // 3. Build the instance
        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            // Recursive resolution
            $object = $this->make($concrete, $parameters);
        }

        // 4. If shared, store it.
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Get the concrete type for a given abstract.
     */
    protected function getConcrete(string $abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Determine if the concrete type is buildable.
     */
    protected function isBuildable($concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * Determine if the binding is shared.
     */
    protected function isShared(string $abstract): bool
    {
        return isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared'];
    }

    /**
     * Build an instance of the given type.
     */
    protected function build($concrete, array $parameters = [])
    {
        // If it's a closure, run it.
        if ($concrete instanceof Closure) {
            return $concrete($this, ...$parameters);
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new Exception("Target class [$concrete] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new Exception("Target [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // If no constructor, just new it.
        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies, $parameters);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve the dependencies for the class.
     */
    protected function resolveDependencies(array $dependencies, array $parameters)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // 1. If parameter is manually provided.
            if (array_key_exists($dependency->name, $parameters)) {
                $results[] = $parameters[$dependency->name];
                continue;
            }

            // 2. Reflect on type.
            $type = $dependency->getType();

            // If missing type or built-in (string, int), check for default value.
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                    continue;
                }

                $className = $dependency->getDeclaringClass()->getName();
                throw new Exception("Unresolvable dependency [{$dependency->name}] in class {$className}");
            }

            // 3. Resolve the class dependency.
            try {
                $results[] = $this->make($type->getName());
            } catch (Exception $e) {
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                    continue;
                }
                throw $e;
            }
        }

        return $results;
    }

    /**
     * Remove a resolved instance from the instance cache.
     */
    public function forgetInstance(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }

    /**
     * Flush the container of all bindings and resolved instances.
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
    }

    /**
     * Run a closure within a specific scope, mimicking Spiral's IoC scopes.
     * This method:
     * 1. Backs up current state.
     * 2. Applies the provided bindings.
     * 3. Executes the callback.
     * 4. Cleans up any instances created during the scope (Request Isolation).
     * 5. Restores original bindings and instances.
     *
     * @param array $bindings Array of [Abstract => Concrete]
     * @param callable $callback
     * @return mixed
     */
    public function runScope(array $bindings, callable $callback)
    {
        // 1. Snapshot valid instances to detect new ones (for cleanup)
        $instanceSnapshot = array_keys($this->instances);

        // 2. Backup and Apply Scope Bindings
        $backupBindings = [];
        $backupInstances = [];

        foreach ($bindings as $abstract => $concrete) {
            // Backup existing binding
            if (isset($this->bindings[$abstract])) {
                $backupBindings[$abstract] = $this->bindings[$abstract];
            }

            // Backup existing instance
            if (isset($this->instances[$abstract])) {
                $backupInstances[$abstract] = $this->instances[$abstract];
                // Remove the current instance so the new binding takes effect
                unset($this->instances[$abstract]);
            }

            // Apply new binding/instance
            if (is_object($concrete) && !$concrete instanceof Closure) {
                $this->instance($abstract, $concrete);
            } else {
                // We bind as shared=true (singleton) within the scope by default
                // to allow state ful services during the request
                $this->singleton($abstract, $concrete);
            }
        }

        try {
            return $callback($this);
        } finally {
            // 3. Cleanup: Remove any instances created *during* the scope
            // This ensures request-scoped services don't leak into the next request
            $currentInstances = array_keys($this->instances);
            $newInstances = array_diff($currentInstances, $instanceSnapshot);

            foreach ($newInstances as $abstract) {
                unset($this->instances[$abstract]);
            }

            // 4. Restore Backups
            // Restore instances that were replaced by the scope
            foreach ($backupInstances as $abstract => $instance) {
                $this->instances[$abstract] = $instance;
            }

            // Restore bindings
            foreach ($bindings as $abstract => $concrete) {
                // Remove the scope binding
                unset($this->bindings[$abstract]);

                // Restore original binding if it existed
                if (isset($backupBindings[$abstract])) {
                    $this->bindings[$abstract] = $backupBindings[$abstract];
                }
            }
        }
    }

    /**
     * Get the container's bindings.
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Determine if the given abstract type has been bound.
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
}
