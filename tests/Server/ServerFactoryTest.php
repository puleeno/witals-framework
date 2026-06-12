<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Server;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Application;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Server\ServerFactory;
use Witals\Framework\Server\TraditionalServer;
use Witals\Framework\Server\FrankenPhpServer;
use Witals\Framework\Server\ReactPhpServer;
use Witals\Framework\Server\SwooleServer;
use Witals\Framework\Server\OpenSwooleServer;
use Witals\Framework\Server\RoadRunnerServer;

class ServerFactoryTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
    }

    public function testCreateTraditionalServer(): void
    {
        $server = ServerFactory::create(RuntimeType::TRADITIONAL, $this->app);

        $this->assertInstanceOf(TraditionalServer::class, $server);
    }

    public function testCreateFrankenPhpServer(): void
    {
        $server = ServerFactory::create(RuntimeType::FRANKENPHP, $this->app);

        $this->assertInstanceOf(FrankenPhpServer::class, $server);
    }

    public function testCreateReactPhpServer(): void
    {
        $server = ServerFactory::create(RuntimeType::REACTPHP, $this->app, '127.0.0.1', 8080);

        $this->assertInstanceOf(ReactPhpServer::class, $server);
    }

    public function testCreateSwooleServer(): void
    {
        $server = ServerFactory::create(RuntimeType::SWOOLE, $this->app, '127.0.0.1', 8080, []);

        $this->assertInstanceOf(SwooleServer::class, $server);
    }

    public function testCreateOpenSwooleServer(): void
    {
        $server = ServerFactory::create(RuntimeType::OPENSWOOLE, $this->app, '127.0.0.1', 8080, []);

        $this->assertInstanceOf(OpenSwooleServer::class, $server);
    }

    public function testCreateRoadRunnerServer(): void
    {
        $server = ServerFactory::create(RuntimeType::ROADRUNNER, $this->app);

        $this->assertInstanceOf(RoadRunnerServer::class, $server);
    }

    public function testCreateUsesDefaultHostAndPort(): void
    {
        $server = ServerFactory::create(RuntimeType::REACTPHP, $this->app);

        $this->assertInstanceOf(ReactPhpServer::class, $server);
    }
}
