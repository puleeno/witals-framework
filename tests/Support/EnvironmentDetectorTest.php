<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Support;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Application;
use Witals\Framework\Support\EnvironmentDetector;

class EnvironmentDetectorTest extends TestCase
{
    private EnvironmentDetector $detector;
    private Application $app;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->detector = new EnvironmentDetector($this->app);
    }

    public function testIsModernReturnsTrueForSwoole(): void
    {
        $this->app->method('isSwoole')->willReturn(true);
        $this->app->method('isOpenSwoole')->willReturn(false);

        // Mock extension check
        if (!extension_loaded('swoole')) {
            $this->assertFalse($this->detector->isModern());
        } else {
            $this->assertTrue($this->detector->isModern());
        }
    }

    public function testIsModernReturnsTrueForOpenSwoole(): void
    {
        $this->app->method('isSwoole')->willReturn(false);
        $this->app->method('isOpenSwoole')->willReturn(true);

        // Mock extension check
        if (!extension_loaded('openswoole')) {
            $this->assertFalse($this->detector->isModern());
        } else {
            $this->assertTrue($this->detector->isModern());
        }
    }

    public function testIsModernReturnsFalseForTraditional(): void
    {
        $this->app->method('isSwoole')->willReturn(false);
        $this->app->method('isOpenSwoole')->willReturn(false);

        $this->assertFalse($this->detector->isModern());
    }

    public function testHasAPCuReturnsTrueWhenAvailable(): void
    {
        if (extension_loaded('apcu') && apcu_enabled()) {
            $this->assertTrue($this->detector->hasAPCu());
        } else {
            $this->assertFalse($this->detector->hasAPCu());
        }
    }

    public function testHasAPCuReturnsFalseWhenNotAvailable(): void
    {
        if (!extension_loaded('apcu') || !apcu_enabled()) {
            $this->assertFalse($this->detector->hasAPCu());
        }
    }

    public function testIsRestrictedReturnsTrueWhenNoModernOrAPCu(): void
    {
        $this->app->method('isSwoole')->willReturn(false);
        $this->app->method('isOpenSwoole')->willReturn(false);

        if (!extension_loaded('apcu') || !apcu_enabled()) {
            $this->assertTrue($this->detector->isRestricted());
        }
    }

    public function testIsRestrictedReturnsFalseWhenModern(): void
    {
        $this->app->method('isSwoole')->willReturn(true);
        $this->app->method('isOpenSwoole')->willReturn(false);

        if (extension_loaded('swoole')) {
            $this->assertFalse($this->detector->isRestricted());
        }
    }

    public function testIsRestrictedReturnsFalseWhenHasAPCu(): void
    {
        $this->app->method('isSwoole')->willReturn(false);
        $this->app->method('isOpenSwoole')->willReturn(false);

        if (extension_loaded('apcu') && apcu_enabled()) {
            $this->assertFalse($this->detector->isRestricted());
        }
    }

    public function testGetBestRegistryTypeReturnsSwooleForModern(): void
    {
        $this->app->method('isSwoole')->willReturn(true);
        $this->app->method('isOpenSwoole')->willReturn(false);

        if (extension_loaded('swoole')) {
            $this->assertSame('swoole', $this->detector->getBestRegistryType());
        }
    }

    public function testGetBestRegistryTypeReturnsApcuWhenAvailable(): void
    {
        $this->app->method('isSwoole')->willReturn(false);
        $this->app->method('isOpenSwoole')->willReturn(false);

        if (extension_loaded('apcu') && apcu_enabled()) {
            $this->assertSame('apcu', $this->detector->getBestRegistryType());
        }
    }

    public function testGetBestRegistryTypeReturnsFileAsFallback(): void
    {
        $this->app->method('isSwoole')->willReturn(false);
        $this->app->method('isOpenSwoole')->willReturn(false);

        if (!extension_loaded('apcu') || !apcu_enabled()) {
            $this->assertSame('file', $this->detector->getBestRegistryType());
        }
    }
}
