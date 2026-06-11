<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Console;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Http\RequestHandler;
use Witals\Framework\Contracts\Http\Kernel as KernelContract;
use org\bovigo\vfs\vfsStream;

class MaintenanceModeTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/witals-test-' . uniqid();
        mkdir($this->tmpDir . '/storage/framework', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->rmdir($this->tmpDir);
    }

    public function test_down_command_creates_maintenance_file(): void
    {
        $app = new Application($this->tmpDir);
        $command = new \Witals\Framework\Console\DownCommand($app);
        $command->handle([]);

        $this->assertFileExists($this->tmpDir . '/storage/framework/down');

        $data = unserialize(file_get_contents($this->tmpDir . '/storage/framework/down'));
        $this->assertIsArray($data);
        $this->assertArrayHasKey('time', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertSame(503, $data['status']);
        $this->assertSame('Application is in maintenance mode.', $data['message']);
    }

    public function test_down_command_with_custom_options(): void
    {
        $app = new Application($this->tmpDir);
        $command = new \Witals\Framework\Console\DownCommand($app);
        $command->handle([
            '--message=Custom message',
            '--status=503',
            '--allow=192.168.1.1',
            '--retry=120',
        ]);

        $data = unserialize(file_get_contents($this->tmpDir . '/storage/framework/down'));
        $this->assertSame('Custom message', $data['message']);
        $this->assertSame(503, $data['status']);
        $this->assertSame([0 => '192.168.1.1'], $data['allowed']);
        $this->assertSame(120, $data['retry']);
    }

    public function test_up_command_removes_maintenance_file(): void
    {
        touch($this->tmpDir . '/storage/framework/down');

        $app = new Application($this->tmpDir);
        $command = new \Witals\Framework\Console\UpCommand($app);
        $command->handle([]);

        $this->assertFileDoesNotExist($this->tmpDir . '/storage/framework/down');
    }

    public function test_up_command_when_already_up_does_not_fail(): void
    {
        $app = new Application($this->tmpDir);
        $command = new \Witals\Framework\Console\UpCommand($app);
        $result = $command->handle([]);

        $this->assertSame(0, $result);
    }

    public function test_maintenance_check_returns_null_when_no_file(): void
    {
        $app = new Application($this->tmpDir);
        $kernel = $this->createMock(KernelContract::class);
        $handler = $this->getMockBuilder(RequestHandler::class)
            ->onlyMethods(['checkMaintenanceMode'])
            ->setConstructorArgs([$app, $kernel])
            ->getMock();

        $ref = new \ReflectionMethod($handler, 'checkMaintenanceMode');
        $result = $ref->invoke($handler, new Request('GET', '/'));

        $this->assertNull($result);
    }

    public function test_maintenance_check_returns_response_when_file_exists(): void
    {
        $data = serialize([
            'time' => time(),
            'message' => 'Application is in maintenance mode.',
            'retry' => null,
            'status' => 503,
            'allowed' => [],
        ]);
        file_put_contents($this->tmpDir . '/storage/framework/down', $data);

        $app = new Application($this->tmpDir);
        $kernel = $this->createMock(KernelContract::class);
        $handler = new RequestHandler($app, $kernel);

        $ref = new \ReflectionMethod($handler, 'checkMaintenanceMode');
        $result = $ref->invoke($handler, new Request('GET', '/'));

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(503, $result->getStatusCode());
        $this->assertStringContainsString('Maintenance mode', $result->getContent());
    }

    public function test_bypass_ip_skips_maintenance(): void
    {
        $data = serialize([
            'time' => time(),
            'message' => 'Down for maintenance',
            'retry' => null,
            'status' => 503,
            'allowed' => ['192.168.1.100'],
        ]);
        file_put_contents($this->tmpDir . '/storage/framework/down', $data);

        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';

        $app = new Application($this->tmpDir);
        $kernel = $this->createMock(KernelContract::class);
        $handler = new RequestHandler($app, $kernel);

        $ref = new \ReflectionMethod($handler, 'checkMaintenanceMode');
        $result = $ref->invoke($handler, new Request('GET', '/'));

        $this->assertNull($result);

        unset($_SERVER['REMOTE_ADDR']);
    }

    public function test_middleware_after_response_inherits_maintenance_header(): void
    {
        $data = serialize([
            'time' => time(),
            'message' => 'Down',
            'retry' => 60,
            'status' => 503,
            'allowed' => [],
        ]);
        file_put_contents($this->tmpDir . '/storage/framework/down', $data);

        $app = new Application($this->tmpDir);
        $kernel = $this->createMock(KernelContract::class);
        $handler = new RequestHandler($app, $kernel);

        $ref = new \ReflectionMethod($handler, 'checkMaintenanceMode');
        $result = $ref->invoke($handler, new Request('GET', '/'));

        $this->assertSame('60', $result->headers('Retry-After'));
        $this->assertSame('true', $result->headers('X-Maintenance-Mode'));
    }

    private function rmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir((string) $item);
            } else {
                unlink((string) $item);
            }
        }

        rmdir($dir);
    }
}
