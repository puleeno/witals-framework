<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Server;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Application;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Server\TraditionalServer;

class TraditionalServerTest extends TestCase
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
            ->with(RuntimeType::TRADITIONAL);

        new TraditionalServer($this->app);
    }

    public function testIsStatefulReturnsFalse(): void
    {
        $server = new TraditionalServer($this->app);

        $this->assertFalse($server->isStateful());
    }

    public function testStartBootsApplication(): void
    {
        $this->app->expects($this->once())
            ->method('boot');

        $this->app->expects($this->once())
            ->method('handle')
            ->willReturn($this->createMock(\Witals\Framework\Http\Response::class));

        $this->app->expects($this->once())
            ->method('terminate');

        $server = new TraditionalServer($this->app);
        $server->start();
    }

    public function testStartHandlesRequest(): void
    {
        $response = $this->createMock(\Witals\Framework\Http\Response::class);
        $response->expects($this->once())
            ->method('send');

        $this->app->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        $server = new TraditionalServer($this->app);
        $server->start();
    }

    public function testStartTerminatesApplication(): void
    {
        $response = $this->createMock(\Witals\Framework\Http\Response::class);
        $response->method('send');

        $this->app->expects($this->once())
            ->method('terminate');

        $server = new TraditionalServer($this->app);
        $server->start();
    }
}
