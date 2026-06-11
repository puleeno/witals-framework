<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Runtime;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Contracts\RuntimeType;

class RuntimeTypeTest extends TestCase
{
    public function test_cases_exist(): void
    {
        $this->assertTrue(enum_exists(RuntimeType::class));
        $this->assertSame('traditional', RuntimeType::TRADITIONAL->value);
        $this->assertSame('roadrunner', RuntimeType::ROADRUNNER->value);
        $this->assertSame('frankenphp', RuntimeType::FRANKENPHP->value);
        $this->assertSame('reactphp', RuntimeType::REACTPHP->value);
        $this->assertSame('swoole', RuntimeType::SWOOLE->value);
        $this->assertSame('openswoole', RuntimeType::OPENSWOOLE->value);
    }

    public function test_traditional_is_not_long_running_and_not_async(): void
    {
        $this->assertFalse(RuntimeType::TRADITIONAL->isLongRunning());
        $this->assertFalse(RuntimeType::TRADITIONAL->isAsync());
    }

    public function test_roadrunner_is_long_running_but_not_async(): void
    {
        $this->assertTrue(RuntimeType::ROADRUNNER->isLongRunning());
        $this->assertFalse(RuntimeType::ROADRUNNER->isAsync());
    }

    public function test_frankenphp_is_long_running_but_not_async(): void
    {
        $this->assertTrue(RuntimeType::FRANKENPHP->isLongRunning());
        $this->assertFalse(RuntimeType::FRANKENPHP->isAsync());
    }

    public function test_reactphp_is_long_running_and_async(): void
    {
        $this->assertTrue(RuntimeType::REACTPHP->isLongRunning());
        $this->assertTrue(RuntimeType::REACTPHP->isAsync());
    }

    public function test_swoole_is_long_running_and_async(): void
    {
        $this->assertTrue(RuntimeType::SWOOLE->isLongRunning());
        $this->assertTrue(RuntimeType::SWOOLE->isAsync());
    }

    public function test_openswoole_is_long_running_and_async(): void
    {
        $this->assertTrue(RuntimeType::OPENSWOOLE->isLongRunning());
        $this->assertTrue(RuntimeType::OPENSWOOLE->isAsync());
    }

    public function test_detect_default_to_traditional(): void
    {
        $this->assertSame(RuntimeType::TRADITIONAL, RuntimeType::detect());
    }

    public function test_detect_frankenphp_from_env(): void
    {
        $_SERVER['FRANKENPHP_WORKER'] = '1';
        $this->assertSame(RuntimeType::FRANKENPHP, RuntimeType::detect());
        unset($_SERVER['FRANKENPHP_WORKER']);
    }

    public function test_detect_frankenphp_from_getenv(): void
    {
        putenv('FRANKENPHP_WORKER=1');
        $this->assertSame(RuntimeType::FRANKENPHP, RuntimeType::detect());
        putenv('FRANKENPHP_WORKER');
    }

    public function test_detect_roadrunner_from_server(): void
    {
        $_SERVER['RR_MODE'] = 'http';
        $this->assertSame(RuntimeType::ROADRUNNER, RuntimeType::detect());
        unset($_SERVER['RR_MODE']);
    }

    public function test_detect_reactphp_from_env(): void
    {
        putenv('REACTPHP_MODE=true');
        $this->assertSame(RuntimeType::REACTPHP, RuntimeType::detect());
        putenv('REACTPHP_MODE');
    }

    public function test_detect_precedence_frankenphp_over_roadrunner(): void
    {
        $_SERVER['FRANKENPHP_WORKER'] = '1';
        $_SERVER['RR_MODE'] = 'http';
        $this->assertSame(RuntimeType::FRANKENPHP, RuntimeType::detect());
        unset($_SERVER['FRANKENPHP_WORKER'], $_SERVER['RR_MODE']);
    }
}
