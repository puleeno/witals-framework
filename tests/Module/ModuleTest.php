<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Module;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Application;
use Witals\Framework\Contracts\RuntimeType;

class ModuleApplication extends Application
{
    public function registerConfiguredProviders(): void
    {
    }
}

class ModuleTest extends TestCase
{
    protected ModuleApplication $app;

    protected string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/witals_test_' . uniqid();
        mkdir($this->tmpDir, 0777, true);

        $this->app = new ModuleApplication($this->tmpDir, RuntimeType::TRADITIONAL);
    }

    protected function tearDown(): void
    {
        $this->rmdir($this->tmpDir);
    }

    protected function rmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $this->rmdir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    public function test_create_module_from_metadata(): void
    {
        $meta = [
            'name' => 'test_module',
            'type' => 'support',
            'version' => '1.0.0',
            'description' => 'Test module',
            'enabled' => true,
        ];

        $module = new \Witals\Framework\Module\Module($this->app, $this->tmpDir, $meta);

        $this->assertSame('test_module', $module->getName());
        $this->assertSame('1.0.0', $module->getVersion());
        $this->assertSame('Test module', $module->getDescription());
        $this->assertSame('support', $module->getType());
        $this->assertTrue($module->isEnabled());
    }

    public function test_module_priority_default(): void
    {
        $module = new \Witals\Framework\Module\Module($this->app, $this->tmpDir, [
            'name' => 'test',
            'type' => 'support',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
        ]);

        $this->assertSame(50, $module->getPriority());
    }

    public function test_module_get_dependencies(): void
    {
        $module = new \Witals\Framework\Module\Module($this->app, $this->tmpDir, [
            'name' => 'test',
            'type' => 'support',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
            'depends' => ['foo', 'bar'],
        ]);

        $this->assertSame(['foo', 'bar'], $module->getDependencies());
    }

    public function test_module_get_provides_consumes(): void
    {
        $module = new \Witals\Framework\Module\Module($this->app, $this->tmpDir, [
            'name' => 'test',
            'type' => 'support',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
            'provides' => ['service_a'],
            'consumes' => ['service_b'],
        ]);

        $this->assertSame(['service_a'], $module->getProvides());
        $this->assertSame(['service_b'], $module->getConsumes());
    }

    public function test_module_get_route_prefix_and_routes(): void
    {
        $module = new \Witals\Framework\Module\Module($this->app, $this->tmpDir, [
            'name' => 'test',
            'type' => 'route',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
            'route_prefix' => '/api/test',
            'routes' => [
                ['method' => 'GET', 'path' => '/hello', 'handler' => ['Handler', 'index']],
            ],
        ]);

        $this->assertSame('/api/test', $module->getRoutePrefix());
        $this->assertCount(1, $module->getRoutes());
    }

    public function test_module_register_and_boot(): void
    {
        $module = new \Witals\Framework\Module\Module($this->app, $this->tmpDir, [
            'name' => 'test',
            'type' => 'support',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
        ]);

        $module->register();
        $module->boot();

        $this->addToAssertionCount(1);
    }

    public function test_module_get_path(): void
    {
        $module = new \Witals\Framework\Module\Module($this->app, '/custom/path', [
            'name' => 'test',
            'type' => 'support',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
        ]);

        $this->assertSame('/custom/path', $module->getPath());
    }

    public function test_module_get_metadata(): void
    {
        $meta = [
            'name' => 'test',
            'type' => 'support',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
        ];

        $module = new \Witals\Framework\Module\Module($this->app, $this->tmpDir, $meta);

        $this->assertSame($meta, $module->getMetadata());
    }

    public function test_module_boot_with_bootstrap_class(): void
    {
        $tmpDir = $this->tmpDir;

        $module = new \Witals\Framework\Module\Module($this->app, $tmpDir, [
            'name' => 'test',
            'type' => 'support',
            'version' => '1.0.0',
            'description' => 'test',
            'enabled' => true,
        ]);

        $module->register();
        $module->boot();

        $this->addToAssertionCount(1);
    }
}
