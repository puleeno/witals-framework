<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\View;

use PHPUnit\Framework\TestCase;
use Witals\Framework\View\ViewManager;
use InvalidArgumentException;

class ViewManagerTest extends TestCase
{
    private ViewManager $manager;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/witals_view_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->manager = new ViewManager([$this->tempDir]);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function testConstructorSetsPaths(): void
    {
        $manager = new ViewManager(['/path/to/views']);

        $this->assertInstanceOf(ViewManager::class, $manager);
    }

    public function testMakeReturnsView(): void
    {
        file_put_contents($this->tempDir . '/test.php', 'Hello World');

        $view = $this->manager->make('test');

        $this->assertInstanceOf(\Witals\Framework\Contracts\View\View::class, $view);
    }

    public function testMakeThrowsExceptionWhenViewNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('View [nonexistent] not found');

        $this->manager->make('nonexistent');
    }

    public function testMakeWithNamespace(): void
    {
        $this->manager->addNamespace('admin', $this->tempDir);
        file_put_contents($this->tempDir . '/test.php', 'Admin View');

        $view = $this->manager->make('admin::test');

        $this->assertInstanceOf(\Witals\Framework\Contracts\View\View::class, $view);
    }

    public function testAddNamespace(): void
    {
        $this->manager->addNamespace('admin', '/admin/views');

        $this->expectNotToPerformAssertions();
    }

    public function testShareAddsSharedData(): void
    {
        $this->manager->share('global_key', 'global_value');
        file_put_contents($this->tempDir . '/test.php', 'Hello World');

        $view = $this->manager->make('test');

        $this->assertArrayHasKey('global_key', $view->getData());
    }

    public function testShareWithArray(): void
    {
        $this->manager->share(['key1' => 'value1', 'key2' => 'value2']);
        file_put_contents($this->tempDir . '/test.php', 'Hello World');

        $view = $this->manager->make('test');

        $this->assertArrayHasKey('key1', $view->getData());
        $this->assertArrayHasKey('key2', $view->getData());
    }

    public function testRegisterEngine(): void
    {
        $engine = $this->createMock(\Witals\Framework\Contracts\View\Engine::class);
        $this->manager->registerEngine('custom', $engine);

        $this->expectNotToPerformAssertions();
    }

    public function testAddPath(): void
    {
        $this->manager->addPath('/additional/path');

        $this->expectNotToPerformAssertions();
    }

    public function testMakeWithDottedNotation(): void
    {
        mkdir($this->tempDir . '/subdir', 0755, true);
        file_put_contents($this->tempDir . '/subdir/test.php', 'Hello World');

        $view = $this->manager->make('subdir.test');

        $this->assertInstanceOf(\Witals\Framework\Contracts\View\View::class, $view);
    }

    public function testMakeMergesSharedData(): void
    {
        $this->manager->share('shared_key', 'shared_value');
        file_put_contents($this->tempDir . '/test.php', 'Hello World');

        $view = $this->manager->make('test', ['local_key' => 'local_value']);

        $this->assertArrayHasKey('shared_key', $view->getData());
        $this->assertArrayHasKey('local_key', $view->getData());
    }

    public function testImplementsFactoryContract(): void
    {
        $this->assertInstanceOf(\Witals\Framework\Contracts\View\Factory::class, $this->manager);
    }
}
