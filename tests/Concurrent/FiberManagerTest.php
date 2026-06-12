<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Concurrent;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Concurrent\FiberManager;

class FiberManagerTest extends TestCase
{
    public function test_disabled_mode_runs_tasks_synchronously(): void
    {
        $manager = new FiberManager(false);
        $this->assertFalse($manager->isEnabled());

        $order = [];
        $manager->all([
            'a' => function () use (&$order) {
                $order[] = 'a';
                return 1;
            },
            'b' => function () use (&$order) {
                $order[] = 'b';
                return 2;
            },
        ]);

        $this->assertSame(['a', 'b'], $order);
    }

    public function test_disabled_run_executes_immediately(): void
    {
        $manager = new FiberManager(false);
        $result = $manager->run(fn() => 42);
        $this->assertSame(42, $result);
    }

    public function test_disabled_empty_tasks(): void
    {
        $manager = new FiberManager(false);
        $this->assertSame([], $manager->all([]));
    }

    public function test_enabled_empty_tasks(): void
    {
        $manager = new FiberManager(true);
        $this->assertSame([], $manager->all([]));
    }

    public function test_enabled_is_enabled(): void
    {
        $manager = new FiberManager(true);
        $this->assertTrue($manager->isEnabled());
    }

    public function test_enabled_run_returns_result(): void
    {
        $manager = new FiberManager(true);

        $result = $manager->run(fn() => 42);
        $this->assertSame(42, $result);
    }

    public function test_enabled_all_collects_results(): void
    {
        $manager = new FiberManager(true);

        $results = $manager->all([
            'x' => fn() => 10,
            'y' => fn() => 20,
            'z' => fn() => 30,
        ]);

        $this->assertSame(['x' => 10, 'y' => 20, 'z' => 30], $results);
    }

    public function test_enabled_all_preserves_task_order(): void
    {
        $manager = new FiberManager(true);

        $order = [];
        $manager->all([
            'a' => function () use (&$order) { $order[] = 'a'; return 1; },
            'b' => function () use (&$order) { $order[] = 'b'; return 2; },
        ]);

        $this->assertSame(['a', 'b'], $order);
    }

    public function test_defer_returns_callback_id(): void
    {
        $manager = new FiberManager(true);

        $id = $manager->defer(fn() => null);
        $this->assertNotEmpty($id);
    }

    public function test_delay_returns_callback_id(): void
    {
        $manager = new FiberManager(true);

        $id = $manager->delay(0.1, fn() => null);
        $this->assertNotEmpty($id);
    }

    public function test_repeat_returns_callback_id(): void
    {
        $manager = new FiberManager(true);

        $id = $manager->repeat(0.1, fn() => null);
        $this->assertNotEmpty($id);
    }

    public function test_cancel_does_not_throw(): void
    {
        $manager = new FiberManager(true);

        $id = $manager->delay(0.1, fn() => null);
        $manager->cancel($id);

        $this->assertTrue(true);
    }
}
