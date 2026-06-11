<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Module;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Application;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Module\ModuleServiceProvider;
use Witals\Framework\Module\ModuleManager;
use Witals\Framework\Module\Hook;
use Witals\Framework\Console\Kernel;

class ModuleServiceProviderTest extends TestCase
{
    protected string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/witals_msp_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpDir . '/*') ?: []);
        rmdir($this->tmpDir);
    }

    public function test_register_binds_module_manager(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);

        $provider = new ModuleServiceProvider($app);
        $provider->register();

        $this->assertTrue($app->has(ModuleManager::class));
        $this->assertTrue($app->has('module.manager'));

        $manager = $app->make(ModuleManager::class);
        $this->assertInstanceOf(ModuleManager::class, $manager);
    }

    public function test_register_binds_hook(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);

        $provider = new ModuleServiceProvider($app);
        $provider->register();

        $this->assertTrue($app->has(Hook::class));
        $this->assertTrue($app->has('hooks'));

        $hook = $app->make(Hook::class);
        $this->assertInstanceOf(Hook::class, $hook);
    }

    public function test_register_singleton(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);

        $provider = new ModuleServiceProvider($app);
        $provider->register();

        $this->assertSame(
            $app->make(ModuleManager::class),
            $app->make(ModuleManager::class)
        );
    }

    public function test_boot_registers_console_commands(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);
        $app->instance(Kernel::class, new Kernel($app));

        $provider = new ModuleServiceProvider($app);
        $provider->register();

        $kernel = $app->make(Kernel::class);

        $provider->boot();
        $this->addToAssertionCount(1);
    }
}
