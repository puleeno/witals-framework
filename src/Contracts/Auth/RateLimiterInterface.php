<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts\Auth;

interface RateLimiterInterface
{
    public function attempt(string $key, int $maxAttempts = 5, int $decaySeconds = 60): bool;
    public function tooManyAttempts(string $key, int $maxAttempts = 5): bool;
    public function remainingAttempts(string $key, int $maxAttempts = 5): int;
    public function clear(string $key): void;
    public function availableIn(string $key): int;
}
