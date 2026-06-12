<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Support;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Application;
use Witals\Framework\Support\ServiceProvider;

class ServiceProviderTest extends TestCase
{
    public function testConstructorSetsApp(): void
    {
        $app = $this->createMock(Application::class);
        $provider = $this->getMockForAbstractClass(ServiceProvider::class, [$app]);

        $this->assertInstanceOf(ServiceProvider::class, $provider);
    }

    public function testBootDoesNothingByDefault(): void
    {
        $app = $this->createMock(Application::class);
        $provider = $this->getMockForAbstractClass(ServiceProvider::class, [$app]);

        $this->expectNotToPerformAssertions();

        $provider->boot();
    }

    public function testRegisterIsAbstract(): void
    {
        $app = $this->createMock(Application::class);
        $provider = $this->getMockForAbstractClass(ServiceProvider::class, [$app]);

        $this->expectNotToPerformAssertions();

        $provider->register();
    }
}
