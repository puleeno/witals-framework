<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Queue;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Queue\QueueManager;
use Witals\Framework\Queue\Worker;
use Witals\Framework\Queue\Contracts\JobInterface;

class WorkerTest extends TestCase
{
    public function test_run_job_success(): void
    {
        $manager = new QueueManager(['default' => 'null']);
        $worker = new Worker($manager);

        $job = $this->createMock(JobInterface::class);
        $job->expects($this->once())->method('handle');
        $job->expects($this->once())->method('delete');
        $job->method('isDeleted')->willReturn(false);
        $job->method('isReleased')->willReturn(false);
        $job->method('displayName')->willReturn('TestJob');
        $job->method('jobId')->willReturn('test-123');

        $worker->runJob($job, 'null');
    }

    public function test_run_job_already_deleted(): void
    {
        $manager = new QueueManager(['default' => 'null']);
        $worker = new Worker($manager);

        $job = $this->createMock(JobInterface::class);
        $job->expects($this->once())->method('handle');
        $job->expects($this->never())->method('delete');
        $job->method('isDeleted')->willReturn(true);
        $job->method('isReleased')->willReturn(false);
        $job->method('displayName')->willReturn('TestJob');
        $job->method('jobId')->willReturn('test-123');

        $worker->runJob($job, 'null');
    }

    public function test_run_job_released(): void
    {
        $manager = new QueueManager(['default' => 'null']);
        $worker = new Worker($manager);

        $job = $this->createMock(JobInterface::class);
        $job->expects($this->once())->method('handle');
        $job->expects($this->never())->method('delete');
        $job->method('isDeleted')->willReturn(false);
        $job->method('isReleased')->willReturn(true);
        $job->method('displayName')->willReturn('TestJob');
        $job->method('jobId')->willReturn('test-123');

        $worker->runJob($job, 'null');
    }

    public function test_run_job_failure_releases_on_retry(): void
    {
        $manager = new QueueManager(['default' => 'null']);
        $worker = new Worker($manager);

        $job = $this->createMock(JobInterface::class);
        $job->method('handle')->willThrowException(new \RuntimeException('fail'));
        $job->method('displayName')->willReturn('TestJob');
        $job->method('jobId')->willReturn('test-123');
        $job->method('attempts')->willReturn(1);
        $job->method('maxTries')->willReturn(null);
        $job->method('backoff')->willReturn(null);
        $job->expects($this->once())->method('release');
        $job->expects($this->never())->method('delete');
        $job->expects($this->never())->method('failed');

        $worker->runJob($job, 'null');
    }

    public function test_run_job_failure_deletes_after_max_tries(): void
    {
        $manager = new QueueManager(['default' => 'null']);
        $worker = new Worker($manager);

        $job = $this->createMock(JobInterface::class);
        $job->method('handle')->willThrowException(new \RuntimeException('fail'));
        $job->method('displayName')->willReturn('TestJob');
        $job->method('jobId')->willReturn('test-123');
        $job->method('attempts')->willReturn(3);
        $job->method('maxTries')->willReturn(3);
        $job->method('backoff')->willReturn(null);
        $job->expects($this->once())->method('failed');
        $job->expects($this->once())->method('delete');
        $job->expects($this->never())->method('release');

        $worker->runJob($job, 'null', ['max_tries' => 3]);
    }

    public function test_stop_and_should_quit(): void
    {
        $manager = new QueueManager(['default' => 'null']);
        $worker = new Worker($manager);

        $this->assertFalse($worker->shouldQuit());

        $worker->stop();

        $this->assertTrue($worker->shouldQuit());
    }

    public function test_get_manager(): void
    {
        $manager = new QueueManager(['default' => 'null']);
        $worker = new Worker($manager);

        $this->assertSame($manager, $worker->getManager());
    }
}
