<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Middleware;

use Witals\Framework\Queue\Contracts\JobMiddlewareInterface;
use Witals\Framework\Queue\Contracts\JobInterface;
use Redis;

/**
 * Throttling Middleware
 * 
 * Multi-worker rate limiting using Redis.
 * Useful for limiting requests per domain/proxy in scraping tasks.
 */
class Throttling implements JobMiddlewareInterface
{
    public function __construct(
        protected Redis $redis,
        protected string $key,
        protected int $maxAttempts = 1,
        protected int $decaySeconds = 60
    ) {
    }

    public function handle(object $job, \Closure $next): void
    {
        $cacheKey = "queue:throttle:{$this->key}";
        
        $current = (int) $this->redis->get($cacheKey);

        if ($current >= $this->maxAttempts) {
            if ($job instanceof JobInterface) {
                // Release the job back with a delay
                $job->release($this->decaySeconds);
                return;
            }
            throw new \RuntimeException("Rate limit exceeded for key: {$this->key}");
        }

        // Increment and set expiry if first attempt
        if ($current === 0) {
            $this->redis->setex($cacheKey, $this->decaySeconds, 1);
        } else {
            $this->redis->incr($cacheKey);
        }

        $next($job);
    }
}
