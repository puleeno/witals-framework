<?php

declare(strict_types=1);

namespace Witals\Framework\Queue\Middleware;

use Witals\Framework\Queue\Contracts\JobMiddlewareInterface;

class RateLimited implements JobMiddlewareInterface
{
    public function __construct(
        protected int $maxAttempts = 3,
        protected int $delaySeconds = 5,
    ) {
    }

    public function handle(object $job, \Closure $next): void
    {
        $attempts = method_exists($job, 'attempts') ? $job->attempts() : 0;

        if ($attempts >= $this->maxAttempts) {
            throw new \RuntimeException(sprintf(
                'Rate limit exceeded: max %d attempts, tried %d',
                $this->maxAttempts,
                $attempts,
            ));
        }

        if ($attempts > 0) {
            sleep($this->delaySeconds);
        }

        $next($job);
    }
}
