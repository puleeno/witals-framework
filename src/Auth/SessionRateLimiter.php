<?php

declare(strict_types=1);

namespace Witals\Framework\Auth;

use Witals\Framework\Contracts\Auth\RateLimiterInterface;
use Witals\Framework\Contracts\Session\SessionInterface;

class SessionRateLimiter implements RateLimiterInterface
{
    private const STORAGE_KEY = '_rate_limits';

    public function __construct(
        private SessionInterface $session,
    ) {}

    public function attempt(string $key, int $maxAttempts = 5, int $decaySeconds = 60): bool
    {
        if ($this->tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        $attempts = $this->getAttempts($key);
        $attempts[] = ['time' => time()];

        $this->setAttempts($key, $attempts);

        return true;
    }

    public function tooManyAttempts(string $key, int $maxAttempts = 5): bool
    {
        $this->prune($key);

        $attempts = $this->getAttempts($key);

        return count($attempts) >= $maxAttempts;
    }

    public function remainingAttempts(string $key, int $maxAttempts = 5): int
    {
        $this->prune($key);
        $attempts = $this->getAttempts($key);

        return max(0, $maxAttempts - count($attempts));
    }

    public function clear(string $key): void
    {
        $limits = $this->session->get(self::STORAGE_KEY, []);
        unset($limits[$key]);
        $this->session->set(self::STORAGE_KEY, $limits);
    }

    public function availableIn(string $key): int
    {
        $attempts = $this->getAttempts($key);

        if (empty($attempts)) {
            return 0;
        }

        $oldest = $attempts[0]['time'] ?? 0;

        return max(0, 60 - (time() - $oldest));
    }

    private function getAttempts(string $key): array
    {
        $limits = $this->session->get(self::STORAGE_KEY, []);
        return $limits[$key] ?? [];
    }

    private function setAttempts(string $key, array $attempts): void
    {
        $limits = $this->session->get(self::STORAGE_KEY, []);
        $limits[$key] = $attempts;
        $this->session->set(self::STORAGE_KEY, $limits);
    }

    private function prune(string $key): void
    {
        $attempts = $this->getAttempts($key);
        $cutoff = time() - 60;

        $attempts = array_values(array_filter($attempts, fn(array $a) => ($a['time'] ?? 0) >= $cutoff));

        $this->setAttempts($key, $attempts);
    }
}
