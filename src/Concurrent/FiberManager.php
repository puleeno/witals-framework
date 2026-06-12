<?php

declare(strict_types=1);

namespace Witals\Framework\Concurrent;

use Witals\Framework\Contracts\ConcurrentManager;
use Revolt\EventLoop;

/**
 * Fiber-based concurrent task executor backed by Revolt's event loop.
 *
 * In long-running runtimes (RoadRunner, Swoole, ReactPHP) tasks are
 * scheduled via the event loop and may suspend/resume cooperatively.
 *
 * In traditional (PHP-FPM) mode, all calls execute synchronously.
 *
 * ⚠ HTTP concurrency with Symfony's HttpClient is handled at the
 * transport layer by curl_multi — start all requests then use
 * stream() to collect responses as they complete.  True Fiber-level
 * I/O concurrency requires an async HTTP client (AMPHP, React) or
 * an async PDO driver, which can be plugged in when needed.
 */
class FiberManager implements ConcurrentManager
{
    private bool $enabled;

    public function __construct(bool $enabled = false)
    {
        $this->enabled = $enabled && class_exists(EventLoop::class);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Run a single task via the event loop.
     * In traditional mode the callable is invoked directly.
     */
    public function run(callable $fn): mixed
    {
        if (!$this->enabled) {
            return $fn();
        }

        $result = null;
        $done = false;

        EventLoop::defer(function () use ($fn, &$result, &$done) {
            $result = $fn();
            $done = true;
            EventLoop::queue(fn() => EventLoop::getDriver()->stop());
        });

        EventLoop::run();

        return $result;
    }

    /**
     * Schedule multiple tasks via the event loop.
     * Each task runs in FIFO order within the event loop's fiber context.
     *
     * For true concurrent I/O, pass tasks that yield control via
     * EventLoop::getSuspension()->suspend().
     *
     * @param array<string, callable> $tasks  key => () → mixed
     * @return array<string, mixed>
     */
    public function all(array $tasks): array
    {
        if (!$this->enabled || $tasks === []) {
            $results = [];
            foreach ($tasks as $key => $task) {
                $results[$key] = $task();
            }
            return $results;
        }

        $results = [];
        $remaining = count($tasks);

        foreach ($tasks as $key => $task) {
            EventLoop::defer(function () use ($task, $key, &$results, &$remaining) {
                $results[$key] = $task();
                $remaining--;
                if ($remaining <= 0) {
                    EventLoop::queue(fn() => EventLoop::getDriver()->stop());
                }
            });
        }

        EventLoop::run();

        return $results;
    }

    public function repeat(float $interval, callable $callback): string
    {
        return EventLoop::repeat($interval, $callback);
    }

    public function delay(float $delay, callable $callback): string
    {
        return EventLoop::delay($delay, $callback);
    }

    public function defer(callable $callback): string
    {
        return EventLoop::defer($callback);
    }

    public function cancel(string $id): void
    {
        EventLoop::cancel($id);
    }

    public function stop(): void
    {
        EventLoop::queue(fn() => EventLoop::getDriver()->stop());
    }
}
