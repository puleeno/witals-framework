<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Middleware;

use Witals\Framework\Queue\Contracts\JobMiddlewareInterface;
use Witals\Framework\Queue\Contracts\JobInterface;
use Redis;
use Throwable;

/**
 * Circuit Breaker Middleware
 * 
 * Prevents execution of jobs if a service is detected as failing.
 * Automatically opens the circuit after N failures and half-opens after a timeout.
 */
class CircuitBreaker implements JobMiddlewareInterface
{
    public function __construct(
        protected Redis $redis,
        protected string $service,
        protected int $failThreshold = 5,
        protected int $retryTimeout = 300
    ) {
    }

    public function handle(object $job, \Closure $next): void
    {
        $statusKey = "cb:status:{$this->service}";
        $failCountKey = "cb:fails:{$this->service}";

        $status = $this->redis->get($statusKey);

        if ($status === 'open') {
            if ($job instanceof JobInterface) {
                // Return to queue until timeout expires
                $job->release($this->retryTimeout);
                return;
            }
            throw new \RuntimeException("Circuit is open for service: {$this->service}");
        }

        try {
            $next($job);
            
            // Success: reset failures
            $this->redis->del($failCountKey);
        } catch (Throwable $e) {
            $fails = $this->redis->incr($failCountKey);
            $this->redis->expire($failCountKey, $this->retryTimeout * 2);

            if ($fails >= $this->failThreshold) {
                $this->redis->setex($statusKey, $this->retryTimeout, 'open');
            }

            throw $e;
        }
    }
}
