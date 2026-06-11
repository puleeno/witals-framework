<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Queue;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Queue\QueueManager;
use Witals\Framework\Queue\Worker;
use Witals\Framework\Queue\Contracts\FailedJobProviderInterface;
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

    public function test_run_job_failure_logs_to_failed_job_provider(): void
    {
        $provider = $this->createMock(FailedJobProviderInterface::class);
        $provider->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('null'),
                $this->anything(),
                $this->anything(),
                $this->isInstanceOf(\RuntimeException::class),
            );

        $manager = new QueueManager(['default' => 'null']);
        $manager->setFailedJobProvider($provider);
        $worker = new Worker($manager);

        $job = $this->createMock(JobInterface::class);
        $job->method('handle')->willThrowException(new \RuntimeException('fail'));
        $job->method('displayName')->willReturn('TestJob');
        $job->method('jobId')->willReturn('test-123');
        $job->method('attempts')->willReturn(3);
        $job->method('maxTries')->willReturn(3);
        $job->method('backoff')->willReturn(null);
        $job->method('queue')->willReturn('default');
        $job->expects($this->once())->method('failed');
        $job->expects($this->once())->method('delete');

        $worker->runJob($job, 'null', ['max_tries' => 3]);
    }

    public function test_run_job_failure_does_not_log_below_max_tries(): void
    {
        $provider = $this->createMock(FailedJobProviderInterface::class);
        $provider->expects($this->never())->method('log');

        $manager = new QueueManager(['default' => 'null']);
        $manager->setFailedJobProvider($provider);
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

    public function test_run_job_with_middleware_pipeline(): void
    {
        $manager = new QueueManager(['default' => 'null']);
        $worker = new Worker($manager);

        $job = $this->createMock(JobInterface::class);
        $job->method('displayName')->willReturn('TestJob');
        $job->method('jobId')->willReturn('test-123');

        $log = [];

        $middleware1 = new class implements \Witals\Framework\Queue\Contracts\JobMiddlewareInterface {
            public function handle($job, $next) : void {
                $GLOBALS['middleware_log'][] = 'middleware1_before';
                $next($job);
                $GLOBALS['middleware_log'][] = 'middleware1_after';
            }
        };

        $middleware2 = new class implements \Witals\Framework\Queue\Contracts\JobMiddlewareInterface {
            public function handle($job, $next) : void {
                $GLOBALS['middleware_log'][] = 'middleware2_before';
                $next($job);
                $GLOBALS['middleware_log'][] = 'middleware2_after';
            }
        };

        $job->method('middleware')->willReturn([$middleware1, $middleware2]);
        $job->method('handle')->willReturnCallback(function () {
            $GLOBALS['middleware_log'][] = 'job_handle';
        });

        $GLOBALS['middleware_log'] = [];
        $worker->runJob($job, 'null');

        $this->assertEquals([
            'middleware1_before',
            'middleware2_before',
            'job_handle',
            'middleware2_after',
            'middleware1_after'
        ], $GLOBALS['middleware_log']);
        
        unset($GLOBALS['middleware_log']);
    }
}
