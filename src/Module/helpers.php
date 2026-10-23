<?php

use Witals\Framework\Module\Contracts\HookInterface;

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
    function add_action(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): void
    {
        app(HookInterface::class)->addAction($hook, $callback, $priority, $accepted_args);
    }
}

if (!function_exists('do_action')) {
    function do_action(string $hook, mixed ...$args): void
    {
        static $resolved = null;

        $container = app();
        if ($resolved === null || $resolved[0] !== $container) {
            $resolved = [$container, $container->make(HookInterface::class)];
        }

        $resolved[1]->doAction($hook, ...$args);
    }
}

if (!function_exists('add_filter')) {
    function add_filter(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): void
    {
        app(HookInterface::class)->addFilter($hook, $callback, $priority, $accepted_args);
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        // Memo theo container instance: bỏ qua resolve mỗi call (hot path —
        // vài trăm call/render block) nhưng vẫn an toàn nếu container đổi
        // giữa request (test bootstrap).
        static $resolved = null;

        $container = app();
        if ($resolved === null || $resolved[0] !== $container) {
            $resolved = [$container, $container->make(HookInterface::class)];
        }

        return $resolved[1]->applyFilters($hook, $value, ...$args);
    }
}
