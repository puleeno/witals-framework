<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Queue;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Application;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Queue\QueueServiceProvider;
use Witals\Framework\Queue\QueueManager;

class ApplicationWithConfig extends Application
{
    protected array $configCache = [];
    protected array $customConfig;

    public function registerConfiguredProviders(): void
    {
    }

    public function __construct(string $basePath, ?RuntimeType $runtime = null, array $config = [])
    {
        $this->customConfig = $config;
        parent::__construct($basePath, $runtime);
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return $this->customConfig[$key] ?? $default;
    }
}

class QueueServiceProviderTest extends TestCase
{
    protected string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/witals_qsp_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpDir . '/*') ?: []);
        rmdir($this->tmpDir);
    }

    public function test_register_binds_queue_manager(): void
    {
        $app = new ApplicationWithConfig($this->tmpDir, RuntimeType::TRADITIONAL);

        $provider = new QueueServiceProvider($app);
        $provider->register();

        $this->assertTrue($app->has(QueueManager::class));
        $this->assertTrue($app->has('queue'));

        $manager = $app->make(QueueManager::class);
        $this->assertInstanceOf(QueueManager::class, $manager);
    }

    public function test_register_uses_config_when_available(): void
    {
        $app = new ApplicationWithConfig($this->tmpDir, RuntimeType::TRADITIONAL, [
            'queue' => ['default' => 'null'],
        ]);

        // Bind config in container so QueueServiceProvider picks it up
        $app->instance('config', ['queue' => ['default' => 'null']]);

        $provider = new QueueServiceProvider($app);
        $provider->register();

        $manager = $app->make(QueueManager::class);
        $this->assertSame('null', $manager->getConnectionName());
    }

    public function test_register_defaults_to_sync(): void
    {
        $app = new ApplicationWithConfig($this->tmpDir, RuntimeType::TRADITIONAL);

        $provider = new QueueServiceProvider($app);
        $provider->register();

        $manager = $app->make(QueueManager::class);
        $this->assertSame('sync', $manager->getConnectionName());
    }
}
