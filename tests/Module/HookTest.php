<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Module;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Module\Hook;

class HookTest extends TestCase
{
    protected Hook $hook;

    protected function setUp(): void
    {
        $this->hook = new Hook();
    }

    public function test_add_action_and_do_action(): void
    {
        $executed = false;

        $this->hook->addAction('test.hook', function () use (&$executed) {
            $executed = true;
        });

        $this->hook->doAction('test.hook');

        $this->assertTrue($executed);
    }

    public function test_do_action_with_args(): void
    {
        $result = [];

        $this->hook->addAction('test.args', function (string $a, int $b) use (&$result) {
            $result = [$a, $b];
        });

        $this->hook->doAction('test.args', 'hello', 42);

        $this->assertSame(['hello', 42], $result);
    }

    public function test_do_action_unregistered_hook_does_nothing(): void
    {
        $this->hook->doAction('nonexistent');
        $this->addToAssertionCount(1);
    }

    public function test_action_priority_order(): void
    {
        $order = [];

        $this->hook->addAction('test.priority', function () use (&$order) {
            $order[] = 'first';
        }, 10);

        $this->hook->addAction('test.priority', function () use (&$order) {
            $order[] = 'second';
        }, 20);

        $this->hook->doAction('test.priority');

        $this->assertSame(['first', 'second'], $order);
    }

    public function test_add_filter_and_apply_filters(): void
    {
        $this->hook->addFilter('test.filter', function (string $value) {
            return strtoupper($value);
        });

        $result = $this->hook->applyFilters('test.filter', 'hello');

        $this->assertSame('HELLO', $result);
    }

    public function test_apply_filters_with_args(): void
    {
        $this->hook->addFilter('test.greet', function (string $greeting, string $name) {
            return "{$greeting}, {$name}!";
        });

        $result = $this->hook->applyFilters('test.greet', 'Hello', 'World');

        $this->assertSame('Hello, World!', $result);
    }

    public function test_apply_filters_chained(): void
    {
        $this->hook->addFilter('test.chain', function (string $value) {
            return strtoupper($value);
        }, 10);

        $this->hook->addFilter('test.chain', function (string $value) {
            return trim($value);
        }, 5);

        $result = $this->hook->applyFilters('test.chain', '  hello  ');

        $this->assertSame('HELLO', $result);
    }

    public function test_apply_filters_unregistered_hook_returns_original(): void
    {
        $result = $this->hook->applyFilters('nonexistent', 'original');

        $this->assertSame('original', $result);
    }

    public function test_has_action(): void
    {
        $this->assertFalse($this->hook->hasAction('test'));

        $this->hook->addAction('test', function () {});

        $this->assertTrue($this->hook->hasAction('test'));
    }

    public function test_has_filter(): void
    {
        $this->assertFalse($this->hook->hasFilter('test'));

        $this->hook->addFilter('test', function ($v) { return $v; });

        $this->assertTrue($this->hook->hasFilter('test'));
    }

    public function test_remove_action(): void
    {
        $executed = false;
        $callback = function () use (&$executed) {
            $executed = true;
        };

        $this->hook->addAction('test', $callback);
        $this->hook->removeAction('test', $callback);

        $this->hook->doAction('test');

        $this->assertFalse($executed);
    }

    public function test_remove_filter(): void
    {
        $callback = function (string $v) {
            return 'modified';
        };

        $this->hook->addFilter('test', $callback);
        $this->hook->removeFilter('test', $callback);

        $result = $this->hook->applyFilters('test', 'original');

        $this->assertSame('original', $result);
    }

    public function test_remove_action_unregistered_does_nothing(): void
    {
        $this->hook->removeAction('nonexistent', function () {});
        $this->addToAssertionCount(1);
    }

    public function test_remove_filter_unregistered_does_nothing(): void
    {
        $this->hook->removeFilter('nonexistent', function ($v) { return $v; });
        $this->addToAssertionCount(1);
    }

    public function test_multiple_actions_on_same_hook(): void
    {
        $count = 0;

        $this->hook->addAction('test.multi', function () use (&$count) { $count++; });
        $this->hook->addAction('test.multi', function () use (&$count) { $count++; });
        $this->hook->addAction('test.multi', function () use (&$count) { $count++; });

        $this->hook->doAction('test.multi');

        $this->assertSame(3, $count);
    }

    public function test_multiple_filters_on_same_hook(): void
    {
        $this->hook->addFilter('test.multi', fn ($v) => $v . 'a');
        $this->hook->addFilter('test.multi', fn ($v) => $v . 'b');
        $this->hook->addFilter('test.multi', fn ($v) => $v . 'c');

        $result = $this->hook->applyFilters('test.multi', '');

        $this->assertSame('abc', $result);
    }
}
