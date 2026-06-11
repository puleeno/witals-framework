<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Queue;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Queue\Drivers\NullQueue;

class NullQueueTest extends TestCase
{
    protected NullQueue $queue;

    protected function setUp(): void
    {
        $this->queue = new NullQueue();
    }

    public function test_push_returns_id_without_executing(): void
    {
        $executed = false;

        $job = new class($executed) {
            public bool $executed;
            public function __construct(bool &$executed) { $this->executed = &$executed; }
            public function handle(): void { $this->executed = true; }
        };

        $id = $this->queue->push($job);

        $this->assertFalse($executed);
        $this->assertStringStartsWith('null_', $id);
    }

    public function test_push_raw_returns_id(): void
    {
        $id = $this->queue->pushRaw('any-payload');
        $this->assertStringStartsWith('null_', $id);
    }

    public function test_later_returns_id(): void
    {
        $id = $this->queue->later(60, new \stdClass());
        $this->assertStringStartsWith('null_', $id);
    }

    public function test_pop_returns_null(): void
    {
        $this->assertNull($this->queue->pop('default'));
    }

    public function test_bulk_returns_array_of_ids(): void
    {
        $ids = $this->queue->bulk([new \stdClass(), new \stdClass()]);

        $this->assertCount(2, $ids);
        foreach ($ids as $id) {
            $this->assertStringStartsWith('null_', $id);
        }
    }

    public function test_connection_name(): void
    {
        $this->assertSame('null', $this->queue->getConnectionName());

        $this->queue->setConnectionName('secondary');
        $this->assertSame('secondary', $this->queue->getConnectionName());
    }
}
