<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Core;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Core\Core;
use Witals\Framework\Contracts\Container;
use InvalidArgumentException;

class CoreTest extends TestCase
{
    private Core $core;
    private Container $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(Container::class);
        $this->core = new Core($this->container);
    }

    public function testConstructorSetsContainer(): void
    {
        $this->assertInstanceOf(Core::class, $this->core);
    }

    public function testCallWithClassAtMethod(): void
    {
        $instance = new class {
            public function testMethod(): string
            {
                return 'result';
            }
        };

        $this->container->method('make')->willReturn($instance);
        $this->container->method('call')->willReturn('result');

        $result = $this->core->call('TestClass@testMethod');

        $this->assertSame('result', $result);
    }

    public function testCallWithClassDoubleColonMethod(): void
    {
        $instance = new class {
            public function testMethod(): string
            {
                return 'result';
            }
        };

        $this->container->method('make')->willReturn($instance);
        $this->container->method('call')->willReturn('result');

        $result = $this->core->call('TestClass::testMethod');

        $this->assertSame('result', $result);
    }

    public function testCallWithInvokableClass(): void
    {
        $instance = new class {
            public function __invoke(): string
            {
                return 'result';
            }
        };

        $this->container->method('make')->willReturn($instance);
        $this->container->method('call')->willReturn('result');

        $result = $this->core->call('TestClass');

        $this->assertSame('result', $result);
    }

    public function testCallWithParameters(): void
    {
        $instance = new class {
            public function testMethod(string $param): string
            {
                return $param;
            }
        };

        $this->container->method('make')->willReturn($instance);
        $this->container->method('call')->willReturn('value');

        $result = $this->core->call('TestClass@testMethod', ['param' => 'value']);

        $this->assertSame('value', $result);
    }

    public function testCallThrowsExceptionForInvalidAction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to resolve core action: invalid_action');

        $this->core->call('invalid_action');
    }

    public function testImplementsCoreInterface(): void
    {
        $this->assertInstanceOf(\Witals\Framework\Contracts\Core\CoreInterface::class, $this->core);
    }
}
