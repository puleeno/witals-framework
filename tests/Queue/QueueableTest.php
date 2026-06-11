<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Queue;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Queue\Queueable;

class QueueableTest extends TestCase
{
    protected object $instance;

    protected function setUp(): void
    {
        $this->instance = new class {
            use Queueable;
        };
    }

    public function test_defaults_are_null(): void
    {
        $this->assertNull($this->instance->queue);
        $this->assertNull($this->instance->connection);
        $this->assertNull($this->instance->timeout);
        $this->assertNull($this->instance->maxTries);
        $this->assertNull($this->instance->maxExceptions);
        $this->assertNull($this->instance->backoff);
        $this->assertNull($this->instance->delay);
        $this->assertNull($this->instance->uniqueId);
    }

    public function test_on_queue(): void
    {
        $result = $this->instance->onQueue('emails');
        $this->assertSame($result, $this->instance);
        $this->assertSame('emails', $this->instance->queue);
    }

    public function test_on_connection(): void
    {
        $result = $this->instance->onConnection('redis');
        $this->assertSame($result, $this->instance);
        $this->assertSame('redis', $this->instance->connection);
    }

    public function test_timeout(): void
    {
        $result = $this->instance->timeout(30);
        $this->assertSame($result, $this->instance);
        $this->assertSame(30, $this->instance->timeout);
    }

    public function test_max_tries(): void
    {
        $result = $this->instance->maxTries(3);
        $this->assertSame($result, $this->instance);
        $this->assertSame(3, $this->instance->maxTries);
    }

    public function test_max_exceptions(): void
    {
        $result = $this->instance->maxExceptions(5);
        $this->assertSame($result, $this->instance);
        $this->assertSame(5, $this->instance->maxExceptions);
    }

    public function test_backoff(): void
    {
        $result = $this->instance->backoff([2, 5, 10]);
        $this->assertSame($result, $this->instance);
        $this->assertSame([2, 5, 10], $this->instance->backoff);
    }

    public function test_delay(): void
    {
        $result = $this->instance->delay(60);
        $this->assertSame($result, $this->instance);
        $this->assertSame(60, $this->instance->delay);
    }

    public function test_fluent_chaining(): void
    {
        $result = $this->instance
            ->onQueue('high')
            ->onConnection('database')
            ->timeout(120)
            ->maxTries(5)
            ->backoff([5, 10])
            ->delay(30);

        $this->assertSame($result, $this->instance);
        $this->assertSame('high', $this->instance->queue);
        $this->assertSame('database', $this->instance->connection);
        $this->assertSame(120, $this->instance->timeout);
        $this->assertSame(5, $this->instance->maxTries);
        $this->assertSame([5, 10], $this->instance->backoff);
        $this->assertSame(30, $this->instance->delay);
    }
}
