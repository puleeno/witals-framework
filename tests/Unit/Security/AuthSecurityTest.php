<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockBuilder;
use App\Http\Controllers\AuthController;

class AuthSecurityTest extends TestCase
{
    private function callPrivate(object $obj, string $method, array $args): mixed
    {
        $ref = new \ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    private function makeController(): AuthController
    {
        return (new MockBuilder($this, AuthController::class))
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function test_safe_redirect_allows_root_relative(): void
    {
        $result = $this->callPrivate($this->makeController(), 'safeRedirect', ['/dashboard']);
        $this->assertSame('/dashboard', $result);
    }

    public function test_safe_redirect_allows_nested_path(): void
    {
        $result = $this->callPrivate($this->makeController(), 'safeRedirect', ['/admin/users']);
        $this->assertSame('/admin/users', $result);
    }

    public function test_safe_redirect_blocks_protocol_relative(): void
    {
        $result = $this->callPrivate($this->makeController(), 'safeRedirect', ['//evil.com']);
        $this->assertSame('/dashboard', $result);
    }

    public function test_safe_redirect_blocks_absolute_url(): void
    {
        $result = $this->callPrivate($this->makeController(), 'safeRedirect', ['https://evil.com']);
        $this->assertSame('/dashboard', $result);
    }

    public function test_safe_redirect_blocks_double_slash_at_start(): void
    {
        $result = $this->callPrivate($this->makeController(), 'safeRedirect', ['///evil.com']);
        $this->assertSame('/dashboard', $result);
    }

    public function test_phpass_verify_valid_hash(): void
    {
        $result = $this->callPrivate($this->makeController(), 'verifyPhpassHash', [
            'test123',
            '$P$Aemk1UHd/f4VtvPz.uukKsHZEX5B1M/',
        ]);

        $this->assertTrue($result);
    }

    public function test_phpass_verify_wrong_password(): void
    {
        $result = $this->callPrivate($this->makeController(), 'verifyPhpassHash', [
            'wrongpassword',
            '$P$Aemk1UHd/f4VtvPz.uukKsHZEX5B1M/',
        ]);

        $this->assertFalse($result);
    }

    public function test_phpass_verify_rejects_non_phpass_hash(): void
    {
        $result = $this->callPrivate($this->makeController(), 'verifyPhpassHash', [
            'test123',
            '$2y$10$abcdefghijklmnopqrstuvabcdefghijklmnopqrstuvwxyz01',
        ]);

        $this->assertFalse($result);
    }

    public function test_phpass_verify_rejects_invalid_count(): void
    {
        $result = $this->callPrivate($this->makeController(), 'verifyPhpassHash', [
            'test',
            '$P$!abc1234abcdefghijklmnopqrstuvwxyz012345',
        ]);

        $this->assertFalse($result);
    }

    public function test_phpass_verify_rejects_short_salt(): void
    {
        $result = $this->callPrivate($this->makeController(), 'verifyPhpassHash', [
            'test',
            '$P$Babc1234abcdefghijklmnopqrstu',
        ]);

        $this->assertFalse($result);
    }
}
