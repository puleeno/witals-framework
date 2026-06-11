<?php

if (!function_exists('module')) {
    function module(?string $name = null): mixed
    {
        $manager = app(\Witals\Framework\Module\ModuleManager::class);

        if ($name === null) {
            return $manager;
        }

        return $manager->load($name);
    }
}

if (!function_exists('add_action')) {
    function add_action(string $hook, callable $callback, int $priority = 10): void
    {
        app(\Witals\Framework\Module\Hook::class)->addAction($hook, $callback, $priority);
    }
}

if (!function_exists('do_action')) {
    function do_action(string $hook, mixed ...$args): void
    {
        app(\Witals\Framework\Module\Hook::class)->doAction($hook, ...$args);
    }
}

if (!function_exists('add_filter')) {
    function add_filter(string $hook, callable $callback, int $priority = 10): void
    {
        app(\Witals\Framework\Module\Hook::class)->addFilter($hook, $callback, $priority);
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        return app(\Witals\Framework\Module\Hook::class)->applyFilters($hook, $value, ...$args);
    }
}
