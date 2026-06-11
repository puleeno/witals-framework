<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Http;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Http\RequestHandler;
use Witals\Framework\Contracts\Http\Kernel;

class MaintenanceModeTest extends TestCase
{
    protected string $tempStorage;

    protected function setUp(): void
    {
        $this->tempStorage = __DIR__ . '/../storage';
        if (!is_dir($this->tempStorage)) {
            mkdir($this->tempStorage, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempStorage);
    }

    protected function removeDirectory($dir): void
    {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? $this->removeDirectory("$dir/$file") : unlink("$dir/$file");
            }
            rmdir($dir);
        }
    }

    public function test_request_passes_when_no_maintenance_file(): void
    {
        $app = $this->createMock(Application::class);
        $app->method('basePath')->willReturnCallback(fn($p = '') => $this->tempStorage . ($p ? '/' . $p : ''));
        
        $kernel = $this->createMock(Kernel::class);
        $kernel->method('handle')->willReturn(new Response('OK'));

        $handler = new RequestHandler($app, $kernel);
        $request = new Request('GET', '/');

        $response = $handler->handle($request);

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_request_blocked_by_maintenance_mode(): void
    {
        $app = $this->createMock(Application::class);
        $app->method('basePath')->willReturnCallback(fn($p = '') => $this->tempStorage . ($p ? '/' . $p : ''));
        $app->method('lifecycle')->willReturn($this->createMock(\Witals\Framework\Contracts\LifecycleManager::class));
        $app->method('afterRequest');
        
        // Setup maintenance file
        $dir = $this->tempStorage . '/storage/framework';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        
        $data = [
            'time' => time(),
            'message' => 'Service Unavailable',
            'status' => 503,
            'allowed' => [],
        ];
        file_put_contents($dir . '/down', serialize($data));

        $kernel = $this->createMock(Kernel::class);
        $handler = new RequestHandler($app, $kernel);
        $request = new Request('GET', '/');

        $response = $handler->handle($request);

        $this->assertEquals(503, $response->getStatusCode());
        $this->assertStringContainsString('Service Unavailable', $response->getContent());
    }

    public function test_maintenance_mode_allows_specific_ips(): void
    {
        $app = $this->createMock(Application::class);
        $app->method('basePath')->willReturnCallback(fn($p = '') => $this->tempStorage . ($p ? '/' . $p : ''));
        $app->method('lifecycle')->willReturn($this->createMock(\Witals\Framework\Contracts\LifecycleManager::class));
        
        // Setup maintenance file
        $dir = $this->tempStorage . '/storage/framework';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        
        $data = [
            'time' => time(),
            'message' => 'Down',
            'status' => 503,
            'allowed' => ['127.0.0.1'],
        ];
        file_put_contents($dir . '/down', serialize($data));

        $kernel = $this->createMock(Kernel::class);
        $kernel->method('handle')->willReturn(new Response('ALLOWED'));

        $handler = new RequestHandler($app, $kernel);
        $request = new Request('GET', '/');
        
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $response = $handler->handle($request);

        $this->assertEquals('ALLOWED', $response->getContent());
        unset($_SERVER['REMOTE_ADDR']);
    }
}
