<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Console\Commands;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Console\Commands\MakeCommandCommand;
use Witals\Framework\Application;

class MakeCommandCommandTest extends TestCase
{
    protected string $tmpDir;
    protected Application $app;
    protected MakeCommandCommand $command;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/witals_make_command_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);

        $this->app = $this->createMock(Application::class);
        $this->app->method('basePath')->willReturn($this->tmpDir);

        $this->command = new MakeCommandCommand($this->app);
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
        $this->assertSame('make:command', $this->command->name);
        $this->assertSame('Create a new console command', $this->command->description);
        $this->assertSame('Console Command', $this->command->type);
    }

    public function test_getStub_contains_placeholders(): void
    {
        $stub = $this->command->getStub();
        $this->assertStringContainsString('{{ namespace }}', $stub);
        $this->assertStringContainsString('{{ class }}', $stub);
        $this->assertStringContainsString('extends Command', $stub);
        $this->assertStringContainsString('protected string $name', $stub);
        $this->assertStringContainsString('public function handle', $stub);
    }

    public function test_getPath_returns_correct_path(): void
    {
        $result = $this->command->getPath('TestCommand');
        $expected = $this->tmpDir . '/app/Console/Commands/TestCommand.php';
        $this->assertSame($expected, $result);
    }

    public function test_getNamespace_returns_correct_namespace(): void
    {
        $result = $this->command->getNamespace('TestCommand');
        $this->assertSame('App\\Console\\Commands', $result);
    }
}
