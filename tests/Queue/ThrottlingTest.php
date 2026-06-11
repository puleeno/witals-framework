<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Queue;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Queue\Middleware\Throttling;
use Witals\Framework\Queue\Contracts\JobInterface;
use Redis;

class ThrottlingTest extends TestCase
{
    public function test_throttling_allows_execution_when_limit_not_reached(): void
    {
        $redis = $this->createMock(Redis::class);
        $redis->method('get')->willReturn(0);
        $redis->expects($this->once())->method('setex')->with('queue:throttle:test', 60, 1);

        $middleware = new Throttling($redis, 'test', 5, 60);
        
        $job = $this->createMock(\stdClass::class);
        $executed = false;
        
        $next = function () use (&$executed) {
            $executed = true;
        };

        $middleware->handle($job, $next);

        $this->assertTrue($executed);
    }

    public function test_throttling_releases_job_when_limit_exceeded(): void
    {
        $redis = $this->createMock(Redis::class);
        $redis->method('get')->willReturn(5);
        $redis->expects($this->never())->method('setex');

        $middleware = new Throttling($redis, 'test', 5, 60);
        
        $job = $this->createMock(JobInterface::class);
        $job->expects($this->once())->method('release')->with(60);
        
        $executed = false;
        $next = function () use (&$executed) {
            $executed = true;
        };

        $middleware->handle($job, $next);

        $this->assertFalse($executed);
    }
}
