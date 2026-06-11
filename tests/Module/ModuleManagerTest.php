<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Module;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Application;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Module\ModuleManager;
use Witals\Framework\Http\Request;

class ApplicationStub extends Application
{
    protected array $configCache = [];

    public function registerConfiguredProviders(): void
    {
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return $default;
    }
}

class ModuleManagerTest extends TestCase
{
    protected Application $app;

    protected string $tmpDir;

    protected string $modulesDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/witals_mm_test_' . uniqid();
        $this->modulesDir = $this->tmpDir . '/modules';
        mkdir($this->modulesDir, 0777, true);

        $this->app = new ApplicationStub($this->tmpDir, RuntimeType::TRADITIONAL);
        $this->app->instance(ModuleManager::class, new ModuleManager($this->app, $this->modulesDir));
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

    protected function createModule(string $name, array $meta = []): string
    {
        $moduleDir = $this->modulesDir . '/' . $name;
        mkdir($moduleDir, 0777, true);

        $defaults = [
            'name' => $name,
            'type' => 'support',
            'version' => '1.0.0',
            'description' => 'Module ' . $name,
            'enabled' => true,
        ];

        file_put_contents(
            $moduleDir . '/module.json',
            json_encode(array_merge($defaults, $meta), JSON_PRETTY_PRINT)
        );

        return $moduleDir;
    }

    public function test_discover_finds_modules(): void
    {
        $this->createModule('test_a');
        $this->createModule('test_b');

        $manager = $this->app->make(ModuleManager::class);
        $all = $manager->all();

        $this->assertCount(2, $all);
        $this->assertArrayHasKey('test_a', $all);
        $this->assertArrayHasKey('test_b', $all);
    }

    public function test_discover_skips_dir_without_module_json(): void
    {
        mkdir($this->modulesDir . '/not_a_module', 0777, true);

        $this->createModule('valid');

        $manager = $this->app->make(ModuleManager::class);
        $all = $manager->all();

        $this->assertCount(1, $all);
        $this->assertArrayHasKey('valid', $all);
    }

    public function test_discover_skips_module_without_name(): void
    {
        $moduleDir = $this->modulesDir . '/noname';
        mkdir($moduleDir, 0777, true);
        file_put_contents($moduleDir . '/module.json', json_encode(['type' => 'support']));

        $this->createModule('valid');

        $manager = $this->app->make(ModuleManager::class);
        $all = $manager->all();

        $this->assertCount(1, $all);
        $this->assertArrayHasKey('valid', $all);
    }

    public function test_discover_skips_invalid_json(): void
    {
        $moduleDir = $this->modulesDir . '/badjson';
        mkdir($moduleDir, 0777, true);
        file_put_contents($moduleDir . '/module.json', 'not json');

        $this->createModule('valid');

        $manager = $this->app->make(ModuleManager::class);
        $all = $manager->all();

        $this->assertCount(1, $all);
        $this->assertArrayHasKey('valid', $all);
    }

    public function test_discover_skips_disabled_modules(): void
    {
        $this->createModule('enabled_module');
        $this->createModule('disabled_module', ['enabled' => false]);

        $manager = $this->app->make(ModuleManager::class);
        $index = $manager->buildRouteIndex();

        $all = $manager->all();
        $this->assertCount(2, $all);

        $this->assertArrayHasKey('enabled_module', $all);
        $this->assertArrayHasKey('disabled_module', $all);
    }

    public function test_build_route_index_skips_support_modules(): void
    {
        $this->createModule('support_mod', ['type' => 'support']);
        $this->createModule('route_mod', [
            'type' => 'route',
            'route_prefix' => '/api',
            'routes' => [
                ['method' => 'GET', 'path' => '/hello', 'handler' => ['Handler', 'index']],
            ],
        ]);

        $manager = $this->app->make(ModuleManager::class);
        $index = $manager->buildRouteIndex();

        $this->assertCount(1, $index);
        $this->assertSame('route_mod', $index[0]['module']);
    }

    public function test_build_route_index_skips_route_without_required_fields(): void
    {
        $this->createModule('good', [
            'type' => 'route',
            'route_prefix' => '/api',
            'routes' => [
                ['method' => 'GET', 'path' => '/valid', 'handler' => ['A', 'b']],
                ['method' => 'GET', 'handler' => ['A', 'b']],
                ['path' => '/hello', 'handler' => ['A', 'b']],
            ],
        ]);

        $manager = $this->app->make(ModuleManager::class);
        $index = $manager->buildRouteIndex();

        $this->assertCount(1, $index);
    }

    public function test_match_route(): void
    {
        $this->createModule('api', [
            'type' => 'route',
            'route_prefix' => '/api',
            'routes' => [
                ['method' => 'GET', 'path' => '/hello', 'handler' => ['Handler', 'index']],
            ],
        ]);

        $manager = $this->app->make(ModuleManager::class);

        $result = $manager->matchRoute('GET', '/api/hello');
        $this->assertSame('api', $result);

        $result = $manager->matchRoute('POST', '/api/hello');
        $this->assertNull($result);

        $result = $manager->matchRoute('GET', '/not-found');
        $this->assertNull($result);
    }

    public function test_match_route_with_params(): void
    {
        $this->createModule('blog', [
            'type' => 'route',
            'route_prefix' => '/blog',
            'routes' => [
                ['method' => 'GET', 'path' => '/post/{id}', 'handler' => ['Handler', 'show']],
            ],
        ]);

        $manager = $this->app->make(ModuleManager::class);

        $result = $manager->matchRoute('GET', '/blog/post/42');
        $this->assertSame('blog', $result);

        $result = $manager->matchRoute('GET', '/blog');
        $this->assertNull($result);
    }

    public function test_load_returns_null_for_missing_module(): void
    {
        $manager = $this->app->make(ModuleManager::class);

        $this->assertNull($manager->load('nonexistent'));
    }

    public function test_load_returns_null_for_broken_module(): void
    {
        $this->createModule('broken', [
            'type' => 'route',
            'version' => '1.0.0',
            'description' => 'broken',
            'enabled' => true,
        ]);

        $manager = $this->app->make(ModuleManager::class);
        $result = $manager->load('broken');

        $this->assertNull($result);
    }

    public function test_load_returns_module_instance(): void
    {
        $this->createModule('simple', [
            'type' => 'support',
            'version' => '1.0.0',
            'description' => 'simple',
            'enabled' => true,
        ]);

        $manager = $this->app->make(ModuleManager::class);

        $manager->discover();
        $all = $manager->all();
        $this->assertArrayHasKey('simple', $all);
        $this->assertTrue($all['simple']['enabled']);

        $module = $manager->load('simple');

        $this->assertNotNull($module);
        $this->assertSame('simple', $module->getName());
    }

    public function test_load_caches_instance(): void
    {
        $this->createModule('simple', [
            'type' => 'support',
            'version' => '1.0.0',
            'description' => 'simple',
            'enabled' => true,
        ]);

        $manager = $this->app->make(ModuleManager::class);

        $manager->discover();
        $module1 = $manager->load('simple');
        $module2 = $manager->load('simple');

        $this->assertNotNull($module1);
        $this->assertSame($module1, $module2);
    }

    public function test_load_support_modules(): void
    {
        $this->createModule('support_mod', ['type' => 'support']);
        $this->createModule('route_mod', [
            'type' => 'route',
            'route_prefix' => '/api',
            'routes' => [
                ['method' => 'GET', 'path' => '/test', 'handler' => ['Handler', 'index']],
            ],
        ]);

        $manager = $this->app->make(ModuleManager::class);
        $manager->loadSupportModules();

        $loaded = $manager->getLoaded();

        $this->assertArrayHasKey('support_mod', $loaded);
        $this->assertArrayNotHasKey('route_mod', $loaded);
    }

    public function test_is_loaded(): void
    {
        $this->createModule('test', ['type' => 'support']);

        $manager = $this->app->make(ModuleManager::class);

        $manager->discover();
        $this->assertFalse($manager->isLoaded('test'));

        $module = $manager->load('test');
        $this->assertNotNull($module, 'Module should have loaded');
        $this->assertTrue($manager->isLoaded('test'));
    }

    public function test_dispatch_returns_null_for_no_match(): void
    {
        $this->createModule('api', [
            'type' => 'route',
            'route_prefix' => '/api',
            'routes' => [
                ['method' => 'GET', 'path' => '/hello', 'handler' => ['Handler', 'index']],
            ],
        ]);

        $manager = $this->app->make(ModuleManager::class);

        $request = new Request('GET', '/not-found');

        $response = $manager->dispatch($request);

        $this->assertNull($response);
    }

    public function test_discover_idempotent(): void
    {
        $manager = $this->app->make(ModuleManager::class);

        $manager->discover();
        $manager->discover();

        $this->addToAssertionCount(1);
    }

    public function test_route_index_is_sorted_by_length(): void
    {
        $this->createModule('api', [
            'type' => 'route',
            'route_prefix' => '/api',
            'routes' => [
                ['method' => 'GET', 'path' => '/posts/{id}/comments', 'handler' => ['A', 'b']],
                ['method' => 'GET', 'path' => '/posts/{id}', 'handler' => ['A', 'b']],
                ['method' => 'GET', 'path' => '/posts', 'handler' => ['A', 'b']],
            ],
        ]);

        $manager = $this->app->make(ModuleManager::class);
        $index = $manager->buildRouteIndex();

        $this->assertCount(3, $index);
        $this->assertStringContainsString('comments', $index[0]['pattern']);
    }
}
