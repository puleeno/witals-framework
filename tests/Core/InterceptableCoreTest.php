<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Core;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Core\InterceptableCore;
use Witals\Framework\Contracts\Core\CoreInterface;
use Witals\Framework\Contracts\Interceptor\InterceptorInterface;

class InterceptableCoreTest extends TestCase
{
    private InterceptableCore $core;
    private CoreInterface $baseCore;

    protected function setUp(): void
    {
        $this->baseCore = $this->createMock(CoreInterface::class);
        $this->core = new InterceptableCore($this->baseCore);
    }

    public function testConstructorSetsBaseCore(): void
    {
        $this->assertInstanceOf(InterceptableCore::class, $this->core);
    }

    public function testAddInterceptor(): void
    {
        $interceptor = $this->createMock(InterceptorInterface::class);

        $this->core->addInterceptor($interceptor);

        $this->expectNotToPerformAssertions();
    }

    public function testSetInterceptors(): void
    {
        $interceptors = [
            $this->createMock(InterceptorInterface::class),
            $this->createMock(InterceptorInterface::class),
        ];

        $this->core->setInterceptors($interceptors);

        $this->expectNotToPerformAssertions();
    }

    public function testCallWithoutInterceptorsCallsBaseCore(): void
    {
        $this->baseCore->expects($this->once())
            ->method('call')
            ->with('action', ['param' => 'value'])
            ->willReturn('result');

        $result = $this->core->call('action', ['param' => 'value']);

        $this->assertSame('result', $result);
    }

    public function testCallWithInterceptor(): void
    {
        $interceptor = $this->createMock(InterceptorInterface::class);
        $interceptor->expects($this->once())
            ->method('intercept')
            ->with('action', [], $this->baseCore)
            ->willReturn('intercepted_result');

        $this->core->addInterceptor($interceptor);

        $result = $this->core->call('action');

        $this->assertSame('intercepted_result', $result);
    }

    public function testCallWithMultipleInterceptors(): void
    {
        $interceptor1 = $this->createMock(InterceptorInterface::class);
        $interceptor2 = $this->createMock(InterceptorInterface::class);

        $interceptor1->expects($this->once())
            ->method('intercept')
            ->willReturnCallback(function ($action, $params, $next) {
                return 'intercepted1_' . $next->call($action, $params);
            });

        $interceptor2->expects($this->once())
            ->method('intercept')
            ->willReturnCallback(function ($action, $params, $next) {
                return 'intercepted2_' . $next->call($action, $params);
            });

        $this->baseCore->method('call')->willReturn('base');

        $this->core->addInterceptor($interceptor1);
        $this->core->addInterceptor($interceptor2);

        $result = $this->core->call('action');

        $this->assertSame('intercepted1_intercepted2_base', $result);
    }

    public function testImplementsCoreInterface(): void
    {
        $this->assertInstanceOf(CoreInterface::class, $this->core);
    }
}
