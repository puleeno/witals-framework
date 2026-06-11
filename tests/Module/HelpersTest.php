<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Module;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Application;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Module\ModuleManager;
use Witals\Framework\Module\Hook;

class HelpersTest extends TestCase
{
    protected string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/witals_helpers_' . uniqid();
        mkdir($this->tmpDir, 0777, true);

        Application::setInstance(null);
    }

    protected function tearDown(): void
    {
        Application::setInstance(null);
        array_map('unlink', glob($this->tmpDir . '/*') ?: []);
        rmdir($this->tmpDir);
    }

    public function test_module_helper_returns_manager(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);
        $app->instance(ModuleManager::class, new ModuleManager($app, $this->tmpDir));

        $manager = module();
        $this->assertInstanceOf(ModuleManager::class, $manager);
    }

    public function test_module_helper_with_name(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);
        $manager = new ModuleManager($app, $this->tmpDir);
        $app->instance(ModuleManager::class, $manager);

        $result = module('nonexistent');
        $this->assertNull($result);
    }

    public function test_add_action_and_do_action(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);
        $app->instance(Hook::class, new Hook());

        $executed = false;
        add_action('test', function () use (&$executed) {
            $executed = true;
        });

        do_action('test');

        $this->assertTrue($executed);
    }

    public function test_add_filter_and_apply_filters(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);
        $app->instance(Hook::class, new Hook());

        add_filter('test', function (string $v) {
            return strtoupper($v);
        });

        $result = apply_filters('test', 'hello');

        $this->assertSame('HELLO', $result);
    }
}
