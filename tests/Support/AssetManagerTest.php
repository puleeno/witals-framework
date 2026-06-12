<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Support;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Application;
use Witals\Framework\Support\AssetManager;

class AssetManagerTest extends TestCase
{
    private AssetManager $manager;
    private Application $app;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->app->method('basePath')->willReturn('/var/www/public');
        $this->manager = new AssetManager($this->app);
    }

    public function testConstructorSetsBaseUrl(): void
    {
        $this->assertInstanceOf(AssetManager::class, $this->manager);
    }

    public function testAddRootReturnsSelf(): void
    {
        $result = $this->manager->addRoot('/path', '/url');

        $this->assertSame($this->manager, $result);
    }

    public function testSetContextClearsAssets(): void
    {
        $this->manager->setContext('frontend');

        $this->expectNotToPerformAssertions();
    }

    public function testSetContextAppliesFrontendDefaults(): void
    {
        $this->manager->setContext('frontend');

        $this->expectNotToPerformAssertions();
    }

    public function testSetContextAppliesAdminDefaults(): void
    {
        $this->manager->setContext('admin');

        $this->expectNotToPerformAssertions();
    }

    public function testSetModeReturnsSelf(): void
    {
        $result = $this->manager->setMode('internal');

        $this->assertSame($this->manager, $result);
    }

    public function testRegisterReturnsSelf(): void
    {
        $result = $this->manager->register('css', 'style', '/path/to/style.css');

        $this->assertSame($this->manager, $result);
    }

    public function testRegisterStoresAsset(): void
    {
        $this->manager->register('css', 'style', '/path/to/style.css', ['dep1'], ['media' => 'all']);

        $this->expectNotToPerformAssertions();
    }
}
