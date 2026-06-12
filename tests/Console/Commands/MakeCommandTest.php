<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Console\Commands;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Console\Commands\MakeCommand;
use Witals\Framework\Application;

class MakeCommandTest extends TestCase
{
    protected string $tmpDir;
    protected Application $app;
    protected TestMakeCommand $command;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/witals_make_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);

        $this->app = $this->createMock(Application::class);
        $this->app->method('basePath')->willReturn($this->tmpDir);

        $this->command = new TestMakeCommand($this->app);
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

    public function test_handle_returns_error_when_name_is_empty(): void
    {
        $result = $this->command->handle([]);
        $this->assertSame(1, $result);
    }

    public function test_handle_returns_error_when_file_already_exists(): void
    {
        $path = $this->tmpDir . '/test/Test.php';
        mkdir(dirname($path), 0755, true);
        file_put_contents($path, '<?php');

        $result = $this->command->handle(['Test']);
        $this->assertSame(1, $result);
    }

    public function test_handle_creates_file_successfully(): void
    {
        $result = $this->command->handle(['Test']);
        $this->assertSame(0, $result);

        $path = $this->tmpDir . '/test/Test.php';
        $this->assertFileExists($path);
        $this->assertStringContainsString('class Test', file_get_contents($path));
        $this->assertStringContainsString('namespace TestNamespace', file_get_contents($path));
    }

    public function test_handle_creates_directory_if_not_exists(): void
    {
        $result = $this->command->handle(['Test']);
        $this->assertSame(0, $result);

        $dir = $this->tmpDir . '/test';
        $this->assertDirectoryExists($dir);
    }

    public function test_getClassName_returns_basename(): void
    {
        $this->assertSame('Test', $this->command->getClassName('Test'));
        $this->assertSame('Test', $this->command->getClassName('Sub\\Test'));
        $this->assertSame('Test', $this->command->getClassName('Sub/Nested/Test'));
    }
}

class TestMakeCommand extends MakeCommand
{
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    protected function getStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{ namespace }};

class {{ class }}
{
}
PHP;
    }

    protected function getPath(string $name): string
    {
        return $this->app->basePath() . '/test/' . $name . '.php';
    }

    protected function getNamespace(string $name): string
    {
        return 'TestNamespace';
    }

    public function getClassName(string $name): string
    {
        return parent::getClassName($name);
    }
}
