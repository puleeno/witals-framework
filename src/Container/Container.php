<?php

declare(strict_types=1);

namespace Witals\Framework\Container;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

use Witals\Framework\Contracts\Container as ContainerContract;
use Witals\Framework\Container\Exceptions\ContainerException;
use Witals\Framework\Container\Exceptions\BindingResolutionException;
use Witals\Framework\Container\Exceptions\NotFoundException;

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
     * The container's reflection cache.
     */
    protected static array $reflectionCache = [];

    /**
     * The stack of concretes currently being built.
     */
    protected array $buildStack = [];

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
     * "Extend" an abstract type in the container.
     *
     * @param string $abstract
     * @param \Closure $closure
     * @return void
     */
    public function extend(string $abstract, \Closure $closure): void
    {
        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $closure($this->instances[$abstract], $this);

            return;
        }

        if (!isset($this->bindings[$abstract])) {
            throw new NotFoundException("Cannot extend abstract [{$abstract}] because it has not been bound.");
        }

        $concrete = $this->bindings[$abstract]['concrete'];

        $this->bind($abstract, function ($container) use ($abstract, $concrete, $closure) {
            return $closure($container->build($concrete), $container);
        }, $this->isShared($abstract));
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
    public function make(string $abstract, array $parameters = []): mixed
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

    public function call(callable $callback, array $parameters = []): mixed
    {
        $reflection = $this->getCallReflection($callback);
        $dependencies = $reflection->getParameters();
        $instances = $this->resolveDependencies($dependencies, $parameters);

        return call_user_func_array($callback, $instances);
    }

    /**
     * Get the reflection for a callback.
     */
    protected function getCallReflection(callable $callback): \ReflectionFunctionAbstract
    {
        $cacheKey = $this->getCallableCacheKey($callback);

        if ($cacheKey !== null && isset(self::$reflectionCache[$cacheKey])) {
            return self::$reflectionCache[$cacheKey];
        }

        $reflection = match (true) {
            $callback instanceof \Closure => new \ReflectionFunction($callback),
            is_array($callback) => new \ReflectionMethod($callback[0], $callback[1]),
            is_string($callback) && str_contains($callback, '::') => new \ReflectionMethod(...explode('::', $callback)),
            default => new \ReflectionMethod($callback, '__invoke'),
        };

        if ($cacheKey !== null) {
            self::$reflectionCache[$cacheKey] = $reflection;
        }

        return $reflection;
    }

    private function getCallableCacheKey(callable $callback): ?string
    {
        if ($callback instanceof \Closure) {
            return null; // Closures are unique per instance — can't cache
        }

        if (is_array($callback)) {
            $class = is_string($callback[0]) ? $callback[0] : get_class($callback[0]);
            return $class . '@' . $callback[1];
        }

        if (is_string($callback)) {
            return $callback;
        }

        return null;
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

        // Circular dependency detection
        if (in_array($concrete, $this->buildStack)) {
            $path = implode(' -> ', $this->buildStack) . ' -> ' . $concrete;
            throw new BindingResolutionException("Circular dependency detected: {$path}");
        }

        $this->buildStack[] = $concrete;

        try {
            if (isset(self::$reflectionCache[$concrete])) {
                $reflector = self::$reflectionCache[$concrete];
            } else {
                $reflector = new ReflectionClass($concrete);
                self::$reflectionCache[$concrete] = $reflector;
            }

            if (!$reflector->isInstantiable()) {
                throw new BindingResolutionException("Target [$concrete] is not instantiable.");
            }

            $constructor = $reflector->getConstructor();

            // If no constructor, just new it.
            if (is_null($constructor)) {
                array_pop($this->buildStack);
                return new $concrete;
            }

            $dependencies = $constructor->getParameters();
            $instances = $this->resolveDependencies($dependencies, $parameters);

            array_pop($this->buildStack);
            return $reflector->newInstanceArgs($instances);
        } catch (ReflectionException $e) {
            array_pop($this->buildStack);
            throw new BindingResolutionException("Target class [$concrete] does not exist.", 0, $e);
        } catch (\Throwable $e) {
            array_pop($this->buildStack);
            throw $e;
        }
    }

    /**
     * Resolve the dependencies for the class.
     */
    protected function resolveDependencies(array $dependencies, array $parameters)
    {
        $results = [];

        foreach ($dependencies as $index => $dependency) {
            // 1. If parameter is manually provided by name or position.
            if (array_key_exists($dependency->name, $parameters)) {
                $results[] = $parameters[$dependency->name];
                continue;
            }

            if (array_key_exists($index, $parameters)) {
                $results[] = $parameters[$index];
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

                $declaringClass = $dependency->getDeclaringClass();
                $name = $declaringClass ? $declaringClass->getName() : 'Closure/Function';
                throw new ContainerException("Unresolvable dependency [{$dependency->name}] in {$name}");
            }

            // 3. Resolve the class dependency.
            try {
                $results[] = $this->make($type->getName());
            } catch (\Exception $e) {
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
        self::$reflectionCache = [];
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
    public function runScope(array $bindings, callable $callback): mixed
    {
        // 1. Snapshot valid instances to detect new ones (for cleanup)
        $instanceSnapshot = $this->instances;
        $bindingSnapshot = $this->bindings;

        // 2. Apply Scope Bindings
        foreach ($bindings as $abstract => $concrete) {
            // Apply new binding/instance
            if (is_object($concrete) && !$concrete instanceof Closure) {
                $this->instance($abstract, $concrete);
            } else {
                // We bind as shared=true (singleton) within the scope by default
                // to allow stateful services during the request
                $this->singleton($abstract, $concrete);
            }
        }

        try {
            return $callback($this);
        } finally {
            // 3. Restore Snapshots
            $this->instances = $instanceSnapshot;
            $this->bindings = $bindingSnapshot;
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

    /**
     * Get all resolved instances.
     */
    public function getInstances(): array
    {
        return $this->instances;
    }
}
