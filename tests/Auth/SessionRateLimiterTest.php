<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Auth;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Auth\SessionRateLimiter;
use Witals\Framework\Contracts\Session\SessionInterface;

class SessionRateLimiterTest extends TestCase
{
    private SessionRateLimiter $rateLimiter;
    private SessionInterface $session;

    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
        $this->rateLimiter = new SessionRateLimiter($this->session);
    }

    public function testAttemptReturnsTrueWhenUnderLimit(): void
    {
        $this->session->method('get')->willReturn([]);
        $this->session->expects($this->once())->method('set');

        $result = $this->rateLimiter->attempt('test_key', 5, 60);

        $this->assertTrue($result);
    }

    public function testAttemptReturnsFalseWhenOverLimit(): void
    {
        $attempts = array_fill(0, 5, ['time' => time()]);
        $this->session->method('get')->willReturn(['test_key' => $attempts]);

        $result = $this->rateLimiter->attempt('test_key', 5, 60);

        $this->assertFalse($result);
    }

    public function testAttemptIncrementsCounter(): void
    {
        $this->session->method('get')->willReturn([]);
        $this->session->expects($this->once())->method('set')->with(
            '_rate_limits',
            $this->callback(function ($data) {
                return isset($data['test_key']) && count($data['test_key']) === 1;
            })
        );

        $this->rateLimiter->attempt('test_key', 5, 60);
    }

    public function testTooManyAttemptsReturnsTrueWhenOverLimit(): void
    {
        $attempts = array_fill(0, 5, ['time' => time()]);
        $this->session->method('get')->willReturn(['test_key' => $attempts]);

        $result = $this->rateLimiter->tooManyAttempts('test_key', 5);

        $this->assertTrue($result);
    }

    public function testTooManyAttemptsReturnsFalseWhenUnderLimit(): void
    {
        $attempts = array_fill(0, 3, ['time' => time()]);
        $this->session->method('get')->willReturn(['test_key' => $attempts]);

        $result = $this->rateLimiter->tooManyAttempts('test_key', 5);

        $this->assertFalse($result);
    }

    public function testTooManyAttemptsPrunesOldAttempts(): void
    {
        $attempts = [
            ['time' => time() - 120],
            ['time' => time() - 90],
            ['time' => time() - 30],
        ];
        $this->session->method('get')->willReturn(['test_key' => $attempts]);
        $this->session->expects($this->once())->method('set')->with(
            '_rate_limits',
            $this->callback(function ($data) {
                return isset($data['test_key']) && count($data['test_key']) === 1;
            })
        );

        $this->rateLimiter->tooManyAttempts('test_key', 5);
    }

    public function testRemainingAttemptsReturnsCorrectValue(): void
    {
        $attempts = array_fill(0, 2, ['time' => time()]);
        $this->session->method('get')->willReturn(['test_key' => $attempts]);

        $result = $this->rateLimiter->remainingAttempts('test_key', 5);

        $this->assertSame(3, $result);
    }

    public function testRemainingAttemptsReturnsZeroWhenOverLimit(): void
    {
        $attempts = array_fill(0, 6, ['time' => time()]);
        $this->session->method('get')->willReturn(['test_key' => $attempts]);

        $result = $this->rateLimiter->remainingAttempts('test_key', 5);

        $this->assertSame(0, $result);
    }

    public function testClearRemovesAttemptsForKey(): void
    {
        $limits = ['test_key' => [['time' => time()]], 'other_key' => [['time' => time()]]];
        $this->session->method('get')->willReturn($limits);
        $this->session->expects($this->once())->method('set')->with(
            '_rate_limits',
            $this->callback(function ($data) {
                return !isset($data['test_key']) && isset($data['other_key']);
            })
        );

        $this->rateLimiter->clear('test_key');
    }

    public function testAvailableInReturnsZeroWhenNoAttempts(): void
    {
        $this->session->method('get')->willReturn([]);

        $result = $this->rateLimiter->availableIn('test_key');

        $this->assertSame(0, $result);
    }

    public function testAvailableInReturnsTimeUntilOldestExpires(): void
    {
        $attempts = [['time' => time() - 30]];
        $this->session->method('get')->willReturn(['test_key' => $attempts]);

        $result = $this->rateLimiter->availableIn('test_key');

        $this->assertGreaterThan(0, $result);
        $this->assertLessThanOrEqual(30, $result);
    }

    public function testAvailableInReturnsZeroWhenOldestExpired(): void
    {
        $attempts = [['time' => time() - 120]];
        $this->session->method('get')->willReturn(['test_key' => $attempts]);

        $result = $this->rateLimiter->availableIn('test_key');

        $this->assertSame(0, $result);
    }

    public function testDifferentKeysHaveSeparateCounters(): void
    {
        $this->session->method('get')->willReturn([]);
        $this->session->expects($this->exactly(2))->method('set');

        $this->rateLimiter->attempt('key1', 5, 60);
        $this->rateLimiter->attempt('key2', 5, 60);

        $this->assertTrue($this->rateLimiter->attempt('key1', 5, 60));
        $this->assertTrue($this->rateLimiter->attempt('key2', 5, 60));
    }

    public function testPruneRemovesExpiredAttempts(): void
    {
        $attempts = [
            ['time' => time() - 120],
            ['time' => time() - 90],
            ['time' => time() - 30],
            ['time' => time()],
        ];
        $this->session->method('get')->willReturn(['test_key' => $attempts]);
        $this->session->expects($this->once())->method('set')->with(
            '_rate_limits',
            $this->callback(function ($data) {
                return isset($data['test_key']) && count($data['test_key']) === 2;
            })
        );

        $this->rateLimiter->tooManyAttempts('test_key', 5);
    }
}
