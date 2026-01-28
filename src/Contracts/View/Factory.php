<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts\View;

/**
 * View Factory Contract
 * Handles view creation and management
 */
interface Factory
{
    /**
     * Create a new view instance.
     *
     * @param string $view
     * @param array $data
     * @return \Witals\Framework\Contracts\View\View
     */
    public function make(string $view, array $data = []): View;

    /**
     * Check if a view exists.
     *
     * @param string $view
     * @return bool
     */
    public function exists(string $view): bool;

    /**
     * Add a piece of shared data to the environment.
     *
     * @param string|array $key
     * @param mixed $value
     * @return mixed
     */
    public function share(string|array $key, mixed $value = null): mixed;

    /**
     * Add a location to the array of view locations.
     *
     * @param string $location
     * @return void
     */
    public function addLocation(string $location): void;

    /**
     * Add a new namespace to the loader.
     *
     * @param string $namespace
     * @param string|array $hints
     * @return void
     */
    public function addNamespace(string $namespace, string|array $hints): void;
}
