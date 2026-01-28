<?php

declare(strict_types=1);

namespace Witals\Framework\View;

use Witals\Framework\Contracts\View\Factory as FactoryContract;
use Witals\Framework\Contracts\View\View as ViewContract;
use Witals\Framework\Contracts\View\Engine;
use Witals\Framework\View\Engines\PhpEngine;
use InvalidArgumentException;

class ViewManager implements FactoryContract
{
    /**
     * The array of view locations.
     */
    protected array $paths = [];

    /**
     * The array of view namespaces.
     */
    protected array $namespaces = [];

    /**
     * The registered view engines.
     */
    protected array $engines = [];

    /**
     * Data that is shared across all views.
     */
    protected array $shared = [];

    /**
     * The extension to engine mapping.
     */
    protected array $extensions = [
        'php' => 'php',
    ];

    public function __construct(array $paths = [])
    {
        $this->paths = $paths;
        $this->registerDefaultEngines();
    }

    /**
     * Register the default view engines.
     */
    protected function registerDefaultEngines(): void
    {
        $this->registerEngine('php', new PhpEngine());
    }

    /**
     * Create a new view instance.
     */
    public function make(string $view, array $data = []): ViewContract
    {
        $view = str_replace('.', DIRECTORY_SEPARATOR, $view);
        $path = $this->findView($view);

        $data = array_merge($this->shared, $data);

        return new View($view, $path, $data, $this->getEngineFromPath($path));
    }

    /**
     * Find the path for a given view name.
     */
    protected function findView(string $view): string
    {
        if (str_contains($view, '::')) {
            return $this->findNamespaceView($view);
        }

        foreach ($this->paths as $path) {
            foreach (array_keys($this->extensions) as $extension) {
                $file = $path . DIRECTORY_SEPARATOR . $view . '.' . $extension;
                if (file_exists($file)) {
                    return $file;
                }
            }
        }

        throw new InvalidArgumentException("View [{$view}] not found.");
    }

    /**
     * Find a view within a namespace.
     */
    protected function findNamespaceView(string $name): string
    {
        [$namespace, $view] = explode('::', $name);

        if (!isset($this->namespaces[$namespace])) {
            throw new InvalidArgumentException("Namespace [{$namespace}] not registered.");
        }

        $hints = (array) $this->namespaces[$namespace];

        foreach ($hints as $path) {
            foreach (array_keys($this->extensions) as $extension) {
                $file = $path . DIRECTORY_SEPARATOR . $view . '.' . $extension;
                if (file_exists($file)) {
                    return $file;
                }
            }
        }

        throw new InvalidArgumentException("View [{$view}] not found in namespace [{$namespace}].");
    }

    /**
     * Check if a view exists.
     */
    public function exists(string $view): bool
    {
        try {
            $this->findView(str_replace('.', DIRECTORY_SEPARATOR, $view));
            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Add a piece of shared data to the environment.
     */
    public function share(string|array $key, mixed $value = null): mixed
    {
        if (is_array($key)) {
            foreach ($key as $innerKey => $innerValue) {
                $this->shared[$innerKey] = $innerValue;
            }
        } else {
            $this->shared[$key] = $value;
        }

        return $value;
    }

    /**
     * Add a location to the array of view locations.
     */
    public function addLocation(string $location): void
    {
        $this->paths[] = $location;
    }

    /**
     * Add a new namespace to the loader.
     */
    public function addNamespace(string $namespace, string|array $hints): void
    {
        $this->namespaces[$namespace] = (array) $hints;
    }

    /**
     * Register a view engine.
     */
    public function registerEngine(string $extension, Engine $engine): void
    {
        $this->engines[$extension] = $engine;
        $this->extensions[$extension] = $extension;
    }

    /**
     * Get the engine for a given path.
     */
    protected function getEngineFromPath(string $path): Engine
    {
        $extensions = array_keys($this->engines);
        usort($extensions, fn($a, $b) => strlen($b) <=> strlen($a));

        foreach ($extensions as $extension) {
            if (str_ends_with($path, '.' . $extension)) {
                return $this->engines[$extension];
            }
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if (!isset($this->engines[$extension])) {
            throw new InvalidArgumentException("No engine registered for extension [{$extension}].");
        }

        return $this->engines[$extension];
    }
}
