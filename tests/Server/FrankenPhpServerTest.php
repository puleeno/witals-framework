<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Server;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Application;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Server\FrankenPhpServer;

class FrankenPhpServerTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
    }

    public function testConstructorSetsRuntime(): void
    {
        $this->app->expects($this->once())
            ->method('setRuntime')
            ->with(RuntimeType::FRANKENPHP);

        new FrankenPhpServer($this->app);
    }

    public function testIsStatefulReturnsTrue(): void
    {
        $server = new FrankenPhpServer($this->app);

        $this->assertTrue($server->isStateful());
    }

    public function testStartExitsWhenFrankenPhpNotAvailable(): void
    {
        if (function_exists('frankenphp_handle_request')) {
            $this->markTestSkipped('FrankenPHP is available');
        }

        $this->expectOutputRegex('/FrankenPHP is not available/');

        $server = new FrankenPhpServer($this->app);
        $server->start();
    }
}
