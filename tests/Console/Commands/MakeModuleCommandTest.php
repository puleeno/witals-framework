<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Console\Commands;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Console\Commands\MakeModuleCommand;
use Witals\Framework\Application;

class MakeModuleCommandTest extends TestCase
{
    protected string $tmpDir;
    protected Application $app;
    protected MakeModuleCommand $command;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/witals_make_module_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);

        $this->app = $this->createMock(Application::class);
        $this->app->method('basePath')->willReturn($this->tmpDir);

        $this->command = new MakeModuleCommand($this->app);
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
        $this->assertSame('make:module', $this->command->name);
        $this->assertSame('Create a new Witals module', $this->command->description);
        $this->assertSame('Module', $this->command->type);
    }

    public function test_getStub_contains_placeholders(): void
    {
        $stub = $this->command->getStub();
        $this->assertStringContainsString('{{ namespace }}', $stub);
        $this->assertStringContainsString('{{ class }}', $stub);
        $this->assertStringContainsString('extends WitalsModule', $stub);
        $this->assertStringContainsString('public function register', $stub);
        $this->assertStringContainsString('public function boot', $stub);
    }

    public function test_getPath_returns_witals_path_by_default(): void
    {
        $result = $this->command->getPath('Blog');
        $expected = $this->tmpDir . '/framework/witals/modules/Blog/Module.php';
        $this->assertSame($expected, $result);
    }

    public function test_getPath_returns_presto_path_when_is_presto(): void
    {
        $this->command->isPresto = true;
        $result = $this->command->getPath('Blog');
        $expected = $this->tmpDir . '/framework/presto/modules/Blog/Module.php';
        $this->assertSame($expected, $result);
    }

    public function test_getNamespace_returns_witals_namespace_by_default(): void
    {
        $result = $this->command->getNamespace('Blog');
        $this->assertSame('Modules\\Blog', $result);
    }

    public function test_getNamespace_returns_presto_namespace_when_is_presto(): void
    {
        $this->command->isPresto = true;
        $result = $this->command->getNamespace('Blog');
        $this->assertSame('PrestoWorld\\Modules\\Blog', $result);
    }

    public function test_handle_sets_is_presto_when_flag_present(): void
    {
        global $argv;
        $originalArgv = $argv;
        $argv = ['witals', 'make:module', 'Blog', '--presto'];

        $this->command->handle(['Blog']);
        $this->assertTrue($this->command->isPresto);

        $argv = $originalArgv;
    }

    public function test_handle_does_not_set_is_presto_when_flag_absent(): void
    {
        global $argv;
        $originalArgv = $argv;
        $argv = ['witals', 'make:module', 'Blog'];

        $this->command->handle(['Blog']);
        $this->assertFalse($this->command->isPresto);

        $argv = $originalArgv;
    }
}
