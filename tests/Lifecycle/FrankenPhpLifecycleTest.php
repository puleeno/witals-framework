<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Lifecycle;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Lifecycle\FrankenPhpLifecycle;
use Witals\Framework\Lifecycle\LifecycleFactory;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Contracts\LifecycleManager;

class FrankenPhpLifecycleTest extends TestCase
{
    public function test_get_lifecycle_type(): void
    {
        $lifecycle = new FrankenPhpLifecycle();
        $this->assertSame('frankenphp', $lifecycle->getLifecycleType());
    }

    public function test_is_long_running(): void
    {
        $lifecycle = new FrankenPhpLifecycle();
        $this->assertTrue($lifecycle->isLongRunning());
    }

    public function test_lifecycle_implements_interface(): void
    {
        $lifecycle = new FrankenPhpLifecycle();
        $this->assertInstanceOf(LifecycleManager::class, $lifecycle);
    }
}

class LifecycleFactoryTest extends TestCase
{
    public function test_create_by_runtime_frankenphp(): void
    {
        $lifecycle = LifecycleFactory::createByRuntime(RuntimeType::FRANKENPHP);
        $this->assertInstanceOf(FrankenPhpLifecycle::class, $lifecycle);
        $this->assertSame('frankenphp', $lifecycle->getLifecycleType());
    }

    public function test_create_by_runtime_roadrunner(): void
    {
        $lifecycle = LifecycleFactory::createByRuntime(RuntimeType::ROADRUNNER);
        $this->assertSame('roadrunner', $lifecycle->getLifecycleType());
        $this->assertTrue($lifecycle->isLongRunning());
    }

    public function test_create_by_runtime_traditional(): void
    {
        $lifecycle = LifecycleFactory::createByRuntime(RuntimeType::TRADITIONAL);
        $this->assertSame('traditional', $lifecycle->getLifecycleType());
        $this->assertFalse($lifecycle->isLongRunning());
    }

    public function test_create_by_runtime_reactphp(): void
    {
        $lifecycle = LifecycleFactory::createByRuntime(RuntimeType::REACTPHP);
        $this->assertSame('reactphp', $lifecycle->getLifecycleType());
    }

    public function test_create_by_runtime_swoole(): void
    {
        $lifecycle = LifecycleFactory::createByRuntime(RuntimeType::SWOOLE);
        $this->assertSame('swoole', $lifecycle->getLifecycleType());
    }

    public function test_create_by_runtime_openswoole(): void
    {
        $lifecycle = LifecycleFactory::createByRuntime(RuntimeType::OPENSWOOLE);
        $this->assertSame('openswoole', $lifecycle->getLifecycleType());
    }

    public function test_create_traditional(): void
    {
        $lifecycle = LifecycleFactory::createTraditional();
        $this->assertSame('traditional', $lifecycle->getLifecycleType());
    }

    public function test_create_roadrunner(): void
    {
        $lifecycle = LifecycleFactory::createRoadRunner();
        $this->assertSame('roadrunner', $lifecycle->getLifecycleType());
    }

    public function test_create_reactphp(): void
    {
        $lifecycle = LifecycleFactory::createReactPhp();
        $this->assertSame('reactphp', $lifecycle->getLifecycleType());
    }

    public function test_create_swoole(): void
    {
        $lifecycle = LifecycleFactory::createSwoole();
        $this->assertSame('swoole', $lifecycle->getLifecycleType());
    }

    public function test_create_openswoole(): void
    {
        $lifecycle = LifecycleFactory::createOpenSwoole();
        $this->assertSame('openswoole', $lifecycle->getLifecycleType());
    }
}
