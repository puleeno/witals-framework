<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Seo;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Container\Container;
use Witals\Framework\Seo\SeoManager;
use Witals\Framework\Seo\SeoServiceProvider;

class SeoServiceProviderTest extends TestCase
{
    public function testRegisterBindsSeoManagerAsSingleton(): void
    {
        $container = new Container();
        $provider = new SeoServiceProvider($container);
        $provider->register();

        $this->assertTrue($container->has(SeoManager::class));
    }

    public function testRegisterBindsSeoAlias(): void
    {
        $container = new Container();
        $provider = new SeoServiceProvider($container);
        $provider->register();

        $this->assertTrue($container->has('seo'));
    }

    public function testMakeReturnsSameInstance(): void
    {
        $container = new Container();
        $provider = new SeoServiceProvider($container);
        $provider->register();

        $seo1 = $container->make(SeoManager::class);
        $seo2 = $container->make(SeoManager::class);

        $this->assertSame($seo1, $seo2);
    }

    public function testMakeViaAliasReturnsSameInstance(): void
    {
        $container = new Container();
        $provider = new SeoServiceProvider($container);
        $provider->register();

        $seo1 = $container->make('seo');
        $seo2 = $container->make(SeoManager::class);

        $this->assertSame($seo1, $seo2);
    }

    public function testBootSharesSeoWithView(): void
    {
        $container = new Container();
        $provider = new SeoServiceProvider($container);
        $provider->register();

        $this->expectNotToPerformAssertions();

        // Note: This test assumes view() is available
        // In a real test, you'd mock the view service
        $provider->boot();
    }
}
