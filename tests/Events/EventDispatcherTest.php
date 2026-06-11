<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Events;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Events\EventDispatcher;
use Witals\Framework\Events\Event;

class EventDispatcherTest extends TestCase
{
    protected EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    public function test_listen_and_dispatch_with_closure(): void
    {
        $called = false;

        $this->dispatcher->listen(\stdClass::class, function ($event) use (&$called) {
            $called = true;
        });

        $this->dispatcher->dispatch(new \stdClass());

        $this->assertTrue($called);
    }

    public function test_dispatch_passes_event_instance(): void
    {
        $passed = null;

        $this->dispatcher->listen(\stdClass::class, function ($event) use (&$passed) {
            $passed = $event;
        });

        $event = new \stdClass();
        $event->foo = 'bar';
        $this->dispatcher->dispatch($event);

        $this->assertSame($event, $passed);
        $this->assertSame('bar', $passed->foo);
    }

    public function test_multiple_listeners_for_same_event(): void
    {
        $order = [];

        $this->dispatcher->listen(\stdClass::class, function () use (&$order) {
            $order[] = 'first';
        });
        $this->dispatcher->listen(\stdClass::class, function () use (&$order) {
            $order[] = 'second';
        });

        $this->dispatcher->dispatch(new \stdClass());

        $this->assertSame(['first', 'second'], $order);
    }

    public function test_listener_not_called_for_different_event(): void
    {
        $called = false;

        $this->dispatcher->listen('SomeEvent', function () use (&$called) {
            $called = true;
        });

        $this->dispatcher->dispatch(new \stdClass());

        $this->assertFalse($called);
    }

    public function test_has_listeners(): void
    {
        $this->assertFalse($this->dispatcher->hasListeners(\stdClass::class));

        $this->dispatcher->listen(\stdClass::class, function () {});

        $this->assertTrue($this->dispatcher->hasListeners(\stdClass::class));
    }

    public function test_get_listeners(): void
    {
        $listener = function () {};
        $this->assertSame([], $this->dispatcher->getListeners(\stdClass::class));

        $this->dispatcher->listen(\stdClass::class, $listener);

        $this->assertSame([$listener], $this->dispatcher->getListeners(\stdClass::class));
    }

    public function test_remove_listener(): void
    {
        $called = false;
        $listener = function () use (&$called) {
            $called = true;
        };

        $this->dispatcher->listen(\stdClass::class, $listener);
        $this->dispatcher->removeListener(\stdClass::class, $listener);

        $this->assertFalse($this->dispatcher->hasListeners(\stdClass::class));

        $this->dispatcher->dispatch(new \stdClass());
        $this->assertFalse($called);
    }

    public function test_listen_multiple_events(): void
    {
        $called = [];

        $this->dispatcher->listen(
            [\stdClass::class, 'ArrayEvent'],
            function ($event) use (&$called) {
                $called[] = is_object($event) ? get_class($event) : $event;
            }
        );

        $this->dispatcher->dispatch(new \stdClass());

        $this->assertSame([\stdClass::class], $called);
    }

    public function test_dispatch_with_class_method_listener(): void
    {
        $this->dispatcher->listen(
            \stdClass::class,
            [ListenerStub::class, 'handle']
        );

        ListenerStub::$called = false;
        $this->dispatcher->dispatch(new \stdClass());

        $this->assertTrue(ListenerStub::$called);
    }

    public function test_dispatch_reentrancy_guard(): void
    {
        $counter = 0;

        $this->dispatcher->listen(\stdClass::class, function ($event) use (&$counter, &$dispatcher) {
            $counter++;
            $this->dispatcher->dispatch(new \stdClass());
        });

        $this->dispatcher->dispatch(new \stdClass());

        $this->assertSame(1, $counter, 'Should only dispatch once due to re-entrancy guard');
    }

    public function test_dispatch_with_event_subclass(): void
    {
        $called = false;

        $this->dispatcher->listen(TestEvent::class, function (TestEvent $event) use (&$called) {
            $called = true;
        });

        $this->dispatcher->dispatch(new TestEvent());

        $this->assertTrue($called);
    }

    public function test_no_listeners_no_error(): void
    {
        $this->dispatcher->dispatch(new \stdClass());
        $this->addToAssertionCount(1);
    }
}

class ListenerStub
{
    public static bool $called = false;

    public function handle($event): void
    {
        static::$called = true;
    }
}

class TestEvent extends Event
{
}
