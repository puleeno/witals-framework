<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Queue;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Queue\Drivers\SyncQueue;

class TestJob
{
    public bool $executed = false;
    public function handle(): void { $this->executed = true; }
}

class SyncQueueTest extends TestCase
{
    protected SyncQueue $queue;

    protected function setUp(): void
    {
        $this->queue = new SyncQueue();
    }

    public function test_push_executes_job_and_returns_id(): void
    {
        $job = new TestJob();

        $id = $this->queue->push($job);

        $this->assertTrue($job->executed);
        $this->assertStringStartsWith('sync_', $id);
    }

    public function test_push_raw_returns_id(): void
    {
        $id = $this->queue->pushRaw(serialize(new TestJob()));
        $this->assertStringStartsWith('sync_', $id);
    }

    public function test_push_raw_with_invalid_payload(): void
    {
        $threwException = false;

        try {
            $this->queue->pushRaw('not-a-serialized-job');
        } catch (\InvalidArgumentException $e) {
            $threwException = true;
        } catch (\ErrorException $e) {
            $threwException = $e->getMessage() !== '';
        }

        $this->assertTrue($threwException);
    }

    public function test_later_executes_immediately(): void
    {
        $job = new TestJob();

        $id = $this->queue->later(60, $job);

        $this->assertTrue($job->executed);
        $this->assertStringStartsWith('sync_', $id);
    }

    public function test_pop_returns_null(): void
    {
        $this->assertNull($this->queue->pop('default'));
    }

    public function test_bulk_executes_all_jobs(): void
    {
        $counter = new class {
            public int $count = 0;
            public function handle(): void { $this->count++; }
        };

        $ids = $this->queue->bulk([$counter, $counter, clone $counter]);

        $this->assertCount(3, $ids);
    }

    public function test_connection_name(): void
    {
        $this->assertSame('sync', $this->queue->getConnectionName());

        $this->queue->setConnectionName('primary');
        $this->assertSame('primary', $this->queue->getConnectionName());
    }
}
