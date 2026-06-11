<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Queue;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Queue\Job;
use Throwable;

class ConcreteJob extends Job
{
    public bool $handled = false;

    public function handle(): void
    {
        $this->handled = true;
    }
}

class JobTest extends TestCase
{
    public function test_display_name_returns_class(): void
    {
        $job = new ConcreteJob();
        $this->assertSame(ConcreteJob::class, $job->displayName());
    }

    public function test_job_id_default_null(): void
    {
        $job = new ConcreteJob();
        $this->assertNull($job->jobId());
    }

    public function test_set_job_id(): void
    {
        $job = new ConcreteJob();
        $job->setJobId('job-123');
        $this->assertSame('job-123', $job->jobId());
    }

    public function test_failed_does_not_throw(): void
    {
        $job = new ConcreteJob();
        $job->failed($this->createMock(Throwable::class));
        $this->addToAssertionCount(1);
    }

    public function test_handle(): void
    {
        $job = new ConcreteJob();
        $this->assertFalse($job->handled);
        $job->handle();
        $this->assertTrue($job->handled);
    }

    public function test_uses_queueable_trait(): void
    {
        $job = new ConcreteJob();

        $this->assertNull($job->queue);
        $this->assertNull($job->connection);
        $this->assertNull($job->timeout);

        $job->onQueue('high')->onConnection('redis')->timeout(30);

        $this->assertSame('high', $job->queue);
        $this->assertSame('redis', $job->connection);
        $this->assertSame(30, $job->timeout);
    }
}
