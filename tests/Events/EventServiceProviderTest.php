<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Events;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Application;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Events\EventServiceProvider;
use Witals\Framework\Events\Contracts\EventDispatcherInterface;

class ApplicationStub extends Application
{
    public function registerConfiguredProviders(): void
    {
    }
}

class EventServiceProviderTest extends TestCase
{
    protected string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/witals_esp_test_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpDir . '/*') ?: []);
        rmdir($this->tmpDir);
    }

    public function test_register_binds_dispatcher(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);
        $provider = new EventServiceProvider($app);
        $provider->register();

        $this->assertTrue($app->has(EventDispatcherInterface::class));
        $this->assertTrue($app->has('events'));

        $dispatcher = $app->make(EventDispatcherInterface::class);
        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
    }

    public function test_register_returns_same_instance(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);
        $provider = new EventServiceProvider($app);
        $provider->register();

        $d1 = $app->make(EventDispatcherInterface::class);
        $d2 = $app->make(EventDispatcherInterface::class);

        $this->assertSame($d1, $d2);
    }

    public function test_dispatcher_works_after_registration(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);
        $provider = new EventServiceProvider($app);
        $provider->register();

        $dispatcher = $app->make(EventDispatcherInterface::class);
        $called = false;

        $dispatcher->listen(\stdClass::class, function () use (&$called) {
            $called = true;
        });

        $dispatcher->dispatch(new \stdClass());

        $this->assertTrue($called);
    }
}
