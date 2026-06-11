<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Runtime;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Application;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Contracts\LifecycleManager;
use Witals\Framework\Contracts\StateManager;

class ApplicationStub extends Application
{
    public function registerConfiguredProviders(): void
    {
    }
}

class ApplicationTest extends TestCase
{
    protected string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/witals_app_test_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->rmdir($this->tmpDir);
    }

    protected function rmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->rmdir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    // ── Runtime checks ──

    public function test_default_runtime_is_detected(): void
    {
        $app = new ApplicationStub($this->tmpDir);

        $this->assertInstanceOf(RuntimeType::class, $app->getRuntime());
    }

    public function test_set_traditional_runtime(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);

        $this->assertSame(RuntimeType::TRADITIONAL, $app->getRuntime());
        $this->assertTrue($app->isTraditional());
        $this->assertFalse($app->isLongRunning());
        $this->assertFalse($app->isAsync());
        $this->assertFalse($app->isRoadRunner());
        $this->assertFalse($app->isFrankenPHP());
        $this->assertFalse($app->isReactPhp());
        $this->assertFalse($app->isSwoole());
        $this->assertFalse($app->isOpenSwoole());
    }

    public function test_set_frankenphp_runtime(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::FRANKENPHP);

        $this->assertSame(RuntimeType::FRANKENPHP, $app->getRuntime());
        $this->assertTrue($app->isFrankenPHP());
        $this->assertTrue($app->isLongRunning());
        $this->assertFalse($app->isAsync());
    }

    public function test_set_roadrunner_runtime(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::ROADRUNNER);

        $this->assertTrue($app->isRoadRunner());
        $this->assertTrue($app->isLongRunning());
    }

    public function test_set_reactphp_runtime(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::REACTPHP);

        $this->assertTrue($app->isReactPhp());
        $this->assertTrue($app->isLongRunning());
        $this->assertTrue($app->isAsync());
    }

    public function test_set_swoole_runtime(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::SWOOLE);

        $this->assertTrue($app->isSwoole());
        $this->assertTrue($app->isLongRunning());
        $this->assertTrue($app->isAsync());
    }

    public function test_set_openswoole_runtime(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::OPENSWOOLE);

        $this->assertTrue($app->isOpenSwoole());
        $this->assertTrue($app->isLongRunning());
        $this->assertTrue($app->isAsync());
    }

    public function test_set_runtime_after_construction(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);
        $this->assertTrue($app->isTraditional());

        $app->setRuntime(RuntimeType::SWOOLE);
        $this->assertTrue($app->isSwoole());
    }

    public function test_set_roadrunner_mode(): void
    {
        $app = new ApplicationStub($this->tmpDir);
        $app->setRoadRunnerMode(true);
        $this->assertTrue($app->isRoadRunner());

        $app->setRoadRunnerMode(false);
        $this->assertTrue($app->isTraditional());
    }

    // ── Base path ──

    public function test_base_path(): void
    {
        $app = new ApplicationStub($this->tmpDir);

        $this->assertSame($this->tmpDir, $app->basePath());
        $this->assertSame($this->tmpDir . '/config', $app->basePath('config'));
        $this->assertSame($this->tmpDir . '/modules', $app->basePath('modules'));
    }

    public function test_error_log_path(): void
    {
        $app = new ApplicationStub($this->tmpDir);

        $this->assertSame(
            $this->tmpDir . '/storage/logs/witals.log',
            $app->getErrorLogPath()
        );
    }

    // ── Boot lifecycle ──

    public function test_boot(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);

        $booted = false;
        $app->booted(function () use (&$booted) {
            $booted = true;
        });

        $app->boot();

        $this->assertTrue($booted);
    }

    public function test_boot_is_idempotent(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);

        $count = 0;
        $app->booted(function () use (&$count) {
            $count++;
        });

        $app->boot();
        $app->boot();

        $this->assertSame(1, $count);
    }

    public function test_booting_callbacks(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);

        $order = [];
        $app->booting(function () use (&$order) { $order[] = 'booting'; });
        $app->booted(function () use (&$order) { $order[] = 'booted'; });

        $app->boot();

        $this->assertSame(['booting', 'booted'], $order);
    }

    // ── Register provider ──

    public function test_register_provider(): void
    {
        $app = new ApplicationStub($this->tmpDir);

        $provider = new class($app) extends \Witals\Framework\Support\ServiceProvider {
            public function register(): void
            {
                $this->app->instance('test_service', new \stdClass());
            }
        };

        $result = $app->register($provider);

        $this->assertTrue($app->has('test_service'));
        $this->assertSame($result, $provider);
    }

    public function test_register_string_provider(): void
    {
        $app = new ApplicationStub($this->tmpDir);

        $provider = $app->register(\Witals\Framework\Console\ConsoleServiceProvider::class);

        $this->assertInstanceOf(\Witals\Framework\Support\ServiceProvider::class, $provider);
    }

    public function test_register_skips_already_registered(): void
    {
        $app = new ApplicationStub($this->tmpDir);

        $provider = new class($app) extends \Witals\Framework\Support\ServiceProvider {
            public function register(): void
            {
                $this->app->instance('test_svc', new \stdClass());
            }
        };

        $app->register($provider);

        // Second register returns the cached provider
        $result = $app->register($provider);
        $this->assertSame($provider, $result);
    }

    // ── Request handling ──

    public function test_handle_returns_response_when_module_manager_not_bound(): void
    {
        $this->expectException(\Throwable::class);

        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);
        $app->handle(new Request('GET', '/test'));
    }

    public function test_before_request_callbacks(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);

        $called = false;
        $app->beforeRequest(function ($request) use (&$called) {
            $called = true;
        });

        $request = new Request('GET', '/test');
        $app->callBeforeRequestCallbacks($request);

        $this->assertTrue($called);
    }

    public function test_terminating_callbacks(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);

        $called = false;
        $app->terminating(function () use (&$called) {
            $called = true;
        });

        $request = new Request('GET', '/test');
        $response = new Response('ok', 200);
        $app->terminate($request, $response);

        $this->assertTrue($called);
    }

    public function test_after_request_on_traditional_does_nothing(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);

        $request = new Request('GET', '/test');
        $response = new Response('ok', 200);

        // Should not throw
        $app->afterRequest($request, $response);
        $this->addToAssertionCount(1);
    }

    // ── State & Lifecycle ──

    public function test_state_manager(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);

        $state = $app->state();
        $this->assertInstanceOf(StateManager::class, $state);
    }

    public function test_lifecycle_manager(): void
    {
        $app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);

        $lifecycle = $app->lifecycle();
        $this->assertInstanceOf(LifecycleManager::class, $lifecycle);
    }

    public function test_view_factory(): void
    {
        $app = new ApplicationStub($this->tmpDir);

        $view = $app->view();
        $this->assertInstanceOf(\Witals\Framework\Contracts\View\Factory::class, $view);
    }

    public function test_translator(): void
    {
        $app = new ApplicationStub($this->tmpDir);

        $translator = $app->translator();
        $this->assertInstanceOf(\Witals\Framework\Contracts\I18n\Translator::class, $translator);
    }

    // ── Unit tests flag ──

    public function test_is_running_unit_tests(): void
    {
        $app = new ApplicationStub($this->tmpDir);

        $this->assertTrue($app->isRunningUnitTests());
    }
}
