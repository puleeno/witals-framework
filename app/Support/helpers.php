<?php

declare(strict_types=1);

/**
 * Global helper functions available throughout the application.
 */

if (!function_exists('env')) {
    /**
     * Get an environment variable, with an optional default.
     *
     * Reads from the real environment (getenv) with $_ENV / $_SERVER as
     * fallbacks. Returns $default when the variable is not set or is empty.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        // Cast well-known string literals to their typed equivalents
        return match (strtolower((string) $value)) {
            'true',  '(true)'  => true,
            'false', '(false)' => false,
            'null',  '(null)'  => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }
}

if (!function_exists('app')) {
    /**
     * Get the application instance, or resolve a binding from the container.
     *
     * @param  string|null $abstract
     * @param  array       $parameters
     * @return mixed
     */
    function app(?string $abstract = null, array $parameters = []): mixed
    {
        $instance = \Witals\Framework\Container\Container::getInstance();

        if ($abstract === null) {
            return $instance;
        }

        return $instance?->make($abstract, $parameters);
    }
}

if (!function_exists('config')) {
    /**
     * Get a configuration value using dot notation.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    function config(string $key, mixed $default = null): mixed
    {
        $app = \Witals\Framework\Container\Container::getInstance();

        if ($app === null) {
            return $default;
        }

        return $app->config($key, $default);
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the absolute path to the application base directory.
     */
    function base_path(string $path = ''): string
    {
        $app = \Witals\Framework\Container\Container::getInstance();
        return $app?->basePath($path) ?? ($path !== '' ? (getcwd() . DIRECTORY_SEPARATOR . $path) : (string) getcwd());
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the absolute path to the storage directory.
     */
    function storage_path(string $path = ''): string
    {
        $app = \Witals\Framework\Container\Container::getInstance();
        return $app?->storagePath($path) ?? base_path('storage' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }
}
