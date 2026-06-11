<?php

declare(strict_types=1);

namespace Witals\Framework\Module;

use Witals\Framework\Module\Contracts\HookInterface;

/**
 * Hook system inspired by Drupal — actions & filters
 *
 * Actions: "do something" at a specific point
 * Filters: "modify data" at a specific point
 */
class Hook implements HookInterface
{
    protected array $actions = [];

    protected array $filters = [];

    public function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        $this->actions[$hook][$priority][] = $callback;
        ksort($this->actions[$hook]);
    }

    public function doAction(string $hook, mixed ...$args): void
    {
        if (!isset($this->actions[$hook])) {
            return;
        }

        foreach ($this->actions[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }

    public function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        $this->filters[$hook][$priority][] = $callback;
        ksort($this->filters[$hook]);
    }

    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        if (!isset($this->filters[$hook])) {
            return $value;
        }

        array_unshift($args, $value);

        foreach ($this->filters[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $value = $callback(...$args);
                $args[0] = $value;
            }
        }

        return $value;
    }

    public function removeAction(string $hook, callable $callback, int $priority = 10): void
    {
        if (!isset($this->actions[$hook][$priority])) {
            return;
        }

        $this->actions[$hook][$priority] = array_filter(
            $this->actions[$hook][$priority],
            fn ($c) => $c !== $callback,
        );

        if ($this->actions[$hook][$priority] === []) {
            unset($this->actions[$hook][$priority]);
        }

        if ($this->actions[$hook] === []) {
            unset($this->actions[$hook]);
        }
    }

    public function removeFilter(string $hook, callable $callback, int $priority = 10): void
    {
        if (!isset($this->filters[$hook][$priority])) {
            return;
        }

        $this->filters[$hook][$priority] = array_filter(
            $this->filters[$hook][$priority],
            fn ($c) => $c !== $callback,
        );

        if ($this->filters[$hook][$priority] === []) {
            unset($this->filters[$hook][$priority]);
        }

        if ($this->filters[$hook] === []) {
            unset($this->filters[$hook]);
        }
    }

    public function hasAction(string $hook): bool
    {
        return isset($this->actions[$hook]) && $this->actions[$hook] !== [];
    }

    public function hasFilter(string $hook): bool
    {
        return isset($this->filters[$hook]) && $this->filters[$hook] !== [];
    }
}
