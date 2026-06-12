<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Console\Commands;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Console\Commands\MakeBlockCommand;
use Witals\Framework\Application;

class MakeBlockCommandTest extends TestCase
{
    protected string $tmpDir;
    protected Application $app;
    protected MakeBlockCommand $command;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/witals_make_block_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);

        $this->app = $this->createMock(Application::class);
        $this->app->method('basePath')->willReturn($this->tmpDir);

        $this->command = new MakeBlockCommand($this->app);
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
        $this->assertSame('make:block', $this->command->name);
        $this->assertSame('Create a new Gutenberg block renderer', $this->command->description);
        $this->assertSame('Block Renderer', $this->command->type);
    }

    public function test_handle_returns_error_when_block_name_is_empty(): void
    {
        $result = $this->command->handle([]);
        $this->assertSame(1, $result);
    }

    public function test_getClassNameFromBlock_simple_name(): void
    {
        $result = $this->command->getClassNameFromBlock('paragraph');
        $this->assertSame('ParagraphBlock', $result);
    }

    public function test_getClassNameFromBlock_with_namespace(): void
    {
        $result = $this->command->getClassNameFromBlock('core/paragraph');
        $this->assertSame('ParagraphBlock', $result);
    }

    public function test_getClassNameFromBlock_with_hyphens(): void
    {
        $result = $this->command->getClassNameFromBlock('my-custom-block');
        $this->assertSame('MyCustomBlockBlock', $result);
    }

    public function test_getCoreBlockName_simple_name(): void
    {
        $result = $this->command->getCoreBlockName('paragraph');
        $this->assertSame('paragraph', $result);
    }

    public function test_getCoreBlockName_with_namespace(): void
    {
        $result = $this->command->getCoreBlockName('core/paragraph');
        $this->assertSame('paragraph', $result);
    }

    public function test_getNamespace_returns_correct_namespace(): void
    {
        $result = $this->command->getNamespace('test');
        $this->assertSame('PrestoWorld\\Modules\\Gutenberg\\Renderer\\Blocks', $result);
    }

    public function test_getPath_returns_correct_path(): void
    {
        $result = $this->command->getPath('test');
        $expected = $this->tmpDir . '/framework/presto/modules/Gutenberg/Renderer/Blocks/TestBlock.php';
        $this->assertSame($expected, $result);
    }

    public function test_getStub_contains_placeholders(): void
    {
        $stub = $this->command->getStub();
        $this->assertStringContainsString('{{ namespace }}', $stub);
        $this->assertStringContainsString('{{ class }}', $stub);
        $this->assertStringContainsString('extends AbstractBlock', $stub);
        $this->assertStringContainsString('public function render', $stub);
    }
}
