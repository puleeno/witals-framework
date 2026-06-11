<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Queue\Worker;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Queue\QueueManager;
use Witals\Framework\Queue\Worker;
use Witals\Framework\Queue\Contracts\JobInterface;
use Witals\Framework\Queue\Contracts\JobMiddlewareInterface;

class MiddlewareTest extends TestCase
{
    public function test_middleware_is_called_before_handle(): void
    {
        $manager = new QueueManager(['default' => 'null']);
        $worker = new Worker($manager);

        $middleware = $this->createMock(JobMiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('handle')
            ->with(
                $this->isInstanceOf(JobInterface::class),
                $this->isInstanceOf(\Closure::class),
            );

        $job = $this->createMock(JobInterface::class);
        $job->method('middleware')->willReturn([$middleware]);
        $job->expects($this->once())->method('handle');
        $job->method('isDeleted')->willReturn(false);
        $job->method('isReleased')->willReturn(false);
        $job->method('displayName')->willReturn('TestJob');
        $job->method('jobId')->willReturn('test-123');

        $worker->runJob($job, 'null');
    }

    public function test_no_middleware_calls_handle_directly(): void
    {
        $manager = new QueueManager(['default' => 'null']);
        $worker = new Worker($manager);

        $job = $this->createMock(JobInterface::class);
        $job->method('middleware')->willReturn([]);
        $job->expects($this->once())->method('handle');
        $job->method('isDeleted')->willReturn(false);
        $job->method('isReleased')->willReturn(false);
        $job->method('displayName')->willReturn('TestJob');
        $job->method('jobId')->willReturn('test-123');

        $worker->runJob($job, 'null');
    }

    public function test_multiple_middleware_run_in_order(): void
    {
        $manager = new QueueManager(['default' => 'null']);
        $worker = new Worker($manager);

        $order = [];

        $mw1 = new class implements JobMiddlewareInterface {
            public array &$order;
            public function handle(object $job, \Closure $next): void
            {
                $this->order[] = 'mw1';
                $next($job);
            }
        };
        $mw1->order = &$order;

        $mw2 = new class implements JobMiddlewareInterface {
            public array &$order;
            public function handle(object $job, \Closure $next): void
            {
                $this->order[] = 'mw2';
                $next($job);
            }
        };
        $mw2->order = &$order;

        $job = $this->createMock(JobInterface::class);
        $job->method('middleware')->willReturn([$mw1, $mw2]);
        $job->expects($this->once())->method('handle');
        $job->method('isDeleted')->willReturn(false);
        $job->method('isReleased')->willReturn(false);
        $job->method('displayName')->willReturn('TestJob');
        $job->method('jobId')->willReturn('test-123');

        $worker->runJob($job, 'null');

        $this->assertSame(['mw1', 'mw2'], $order);
    }

    public function test_middleware_can_block_job_execution(): void
    {
        $manager = new QueueManager(['default' => 'null']);
        $worker = new Worker($manager);

        $blockingMiddleware = new class implements JobMiddlewareInterface {
            public function handle(object $job, \Closure $next): void
            {
                throw new \RuntimeException('Blocked by middleware');
            }
        };

        $job = $this->createMock(JobInterface::class);
        $job->method('middleware')->willReturn([$blockingMiddleware]);
        $job->expects($this->never())->method('handle');
        $job->method('displayName')->willReturn('TestJob');
        $job->method('jobId')->willReturn('test-123');
        $job->method('attempts')->willReturn(1);
        $job->method('maxTries')->willReturn(null);
        $job->method('backoff')->willReturn(null);
        $job->expects($this->once())->method('release');

        $worker->runJob($job, 'null');
    }

    public function test_middleware_can_modify_job(): void
    {
        $manager = new QueueManager(['default' => 'null']);
        $worker = new Worker($manager);

        $modifyingMiddleware = new class implements JobMiddlewareInterface {
            public function handle(object $job, \Closure $next): void
            {
                $job->modified = true;
                $next($job);
            }
        };

        $job = $this->createMock(JobInterface::class);
        $job->method('middleware')->willReturn([$modifyingMiddleware]);
        $job->method('isDeleted')->willReturn(false);
        $job->method('isReleased')->willReturn(false);
        $job->method('displayName')->willReturn('TestJob');
        $job->method('jobId')->willReturn('test-123');
        $job->expects($this->once())->method('handle');

        $worker->runJob($job, 'null');
    }
}
