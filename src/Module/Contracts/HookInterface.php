<?php

declare(strict_types=1);

namespace Witals\Framework\Module\Contracts;

/**
 * Hook system interface (Drupal-style actions & filters)
 */
interface HookInterface
{
    public function addAction(string $hook, callable $callback, int $priority = 10): void;

    public function doAction(string $hook, mixed ...$args): void;

    public function addFilter(string $hook, callable $callback, int $priority = 10): void;

    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed;

    public function removeAction(string $hook, callable $callback, int $priority = 10): void;

    public function removeFilter(string $hook, callable $callback, int $priority = 10): void;

    public function hasAction(string $hook): bool;

    public function hasFilter(string $hook): bool;
}
