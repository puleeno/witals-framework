<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\State;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Application;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\State\StateManagerFactory;
use Witals\Framework\State\StatefulManager;
use Witals\Framework\State\StatelessManager;

class StateManagerFactoryTest extends TestCase
{
    public function testCreateStatelessReturnsStatelessManager(): void
    {
        $manager = StateManagerFactory::createStateless();

        $this->assertInstanceOf(StatelessManager::class, $manager);
    }

    public function testCreateStatefulReturnsStatefulManager(): void
    {
        $manager = StateManagerFactory::createStateful();

        $this->assertInstanceOf(StatefulManager::class, $manager);
    }

    public function testCreateByRuntimeForTraditionalReturnsStateless(): void
    {
        $manager = StateManagerFactory::createByRuntime(RuntimeType::TRADITIONAL);

        $this->assertInstanceOf(StatelessManager::class, $manager);
    }

    public function testCreateByRuntimeForFrankenPhpReturnsStateful(): void
    {
        $manager = StateManagerFactory::createByRuntime(RuntimeType::FRANKENPHP);

        $this->assertInstanceOf(StatefulManager::class, $manager);
    }

    public function testCreateByRuntimeForRoadRunnerReturnsStateful(): void
    {
        $manager = StateManagerFactory::createByRuntime(RuntimeType::ROADRUNNER);

        $this->assertInstanceOf(StatefulManager::class, $manager);
    }

    public function testCreateByRuntimeForSwooleReturnsStateful(): void
    {
        $manager = StateManagerFactory::createByRuntime(RuntimeType::SWOOLE);

        $this->assertInstanceOf(StatefulManager::class, $manager);
    }

    public function testCreateByRuntimeForOpenSwooleReturnsStateful(): void
    {
        $manager = StateManagerFactory::createByRuntime(RuntimeType::OPENSWOOLE);

        $this->assertInstanceOf(StatefulManager::class, $manager);
    }

    public function testCreateByRuntimeForReactPhpReturnsStateful(): void
    {
        $manager = StateManagerFactory::createByRuntime(RuntimeType::REACTPHP);

        $this->assertInstanceOf(StatefulManager::class, $manager);
    }
}
