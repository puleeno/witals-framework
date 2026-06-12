<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Console\Commands;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Console\Commands\MakeProviderCommand;
use Witals\Framework\Application;

class MakeProviderCommandTest extends TestCase
{
    protected string $tmpDir;
    protected Application $app;
    protected MakeProviderCommand $command;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/witals_make_provider_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);

        $this->app = $this->createMock(Application::class);
        $this->app->method('basePath')->willReturn($this->tmpDir);

        $this->command = new MakeProviderCommand($this->app);
    }

    protected function tearDown(): void
    {
        $this->rmdir($this->tmpDir);
    }

    protected function rmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rmdir($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function test_command_properties(): void
    {
        $this->assertSame('make:provider', $this->command->name);
        $this->assertSame('Create a new service provider', $this->command->description);
        $this->assertSame('Service Provider', $this->command->type);
    }

    public function test_getStub_contains_placeholders(): void
    {
        $stub = $this->command->getStub();
        $this->assertStringContainsString('{{ namespace }}', $stub);
        $this->assertStringContainsString('{{ class }}', $stub);
        $this->assertStringContainsString('extends ServiceProvider', $stub);
        $this->assertStringContainsString('public function register', $stub);
        $this->assertStringContainsString('public function boot', $stub);
    }

    public function test_getPath_returns_foundation_path_by_default(): void
    {
        global $argv;
        $originalArgv = $argv;
        $argv = ['witals', 'make:provider', 'TestProvider'];

        $result = $this->command->getPath('TestProvider');
        $expected = $this->tmpDir . '/framework/presto/Foundation/Providers/TestProvider.php';
        $this->assertSame($expected, $result);

        $argv = $originalArgv;
    }

    public function test_getPath_returns_module_path_when_module_option(): void
    {
        global $argv;
        $originalArgv = $argv;
        $argv = ['witals', 'make:provider', 'TestProvider', '--module=Blog'];

        $result = $this->command->getPath('TestProvider');
        $expected = $this->tmpDir . '/framework/presto/modules/Blog/Providers/TestProvider.php';
        $this->assertSame($expected, $result);

        $argv = $originalArgv;
    }

    public function test_getNamespace_returns_foundation_namespace_by_default(): void
    {
        global $argv;
        $originalArgv = $argv;
        $argv = ['witals', 'make:provider', 'TestProvider'];

        $result = $this->command->getNamespace('TestProvider');
        $this->assertSame('PrestoWorld\\Foundation\\Providers', $result);

        $argv = $originalArgv;
    }

    public function test_getNamespace_returns_module_namespace_when_module_option(): void
    {
        global $argv;
        $originalArgv = $argv;
        $argv = ['witals', 'make:provider', 'TestProvider', '--module=Blog'];

        $result = $this->command->getNamespace('TestProvider');
        $this->assertSame('PrestoWorld\\Modules\\Blog\\Providers', $result);

        $argv = $originalArgv;
    }
}
