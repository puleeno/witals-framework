<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Contracts\ResettableInterface;
use App\Http\Routing\RouteRegistry;
use App\Http\Routing\Contracts\RouteRegistryInterface;

class RouteRegistryTest extends TestCase
{
    private RouteRegistry $registry;
    private Application $app;

    protected function setUp(): void
    {
        $this->app = new class(sys_get_temp_dir(), RuntimeType::TRADITIONAL) extends Application {
            public function registerConfiguredProviders(): void {}
        };
        Application::setInstance($this->app);
        $this->registry = new RouteRegistry($this->app);
    }

    protected function tearDown(): void
    {
        Application::setInstance(null);
    }

    private static function req(string $method = 'GET', string $path = '/'): Request
    {
        return new Request($method, $path);
    }

    public function test_implements_interface(): void
    {
        $this->assertInstanceOf(RouteRegistryInterface::class, $this->registry);
    }

    public function test_implements_resettable_interface(): void
    {
        $this->assertInstanceOf(ResettableInterface::class, $this->registry);
    }

    public function test_add_and_get_routes(): void
    {
        $this->registry->addRoute('GET', '/test', fn() => 'ok');
        $routes = $this->registry->getAll();
        $this->assertCount(1, $routes);
        $this->assertSame('/test', $routes[0]['path']);
        $this->assertSame('GET', $routes[0]['method']);
    }

    public function test_add_route_normalizes_path(): void
    {
        $this->registry->addRoute('GET', 'test', fn() => 'ok');
        $routes = $this->registry->getAll();
        $this->assertSame('/test', $routes[0]['path']);
    }

    public function test_add_route_uppercases_method(): void
    {
        $this->registry->addRoute('get', '/test', fn() => 'ok');
        $routes = $this->registry->getAll();
        $this->assertSame('GET', $routes[0]['method']);
    }

    public function test_add_route_with_priority(): void
    {
        $this->registry->addRoute('GET', '/module', fn() => 'module', RouteRegistryInterface::PRIORITY_MODULE);
        $this->registry->addRoute('GET', '/native', fn() => 'native', RouteRegistryInterface::PRIORITY_NATIVE);
        $this->assertCount(2, $this->registry->getAll());
    }

    public function test_add_routes_bulk(): void
    {
        $this->registry->addRoutes([
            ['method' => 'GET', 'path' => '/a', 'action' => fn() => 'a'],
            ['method' => 'POST', 'path' => '/b', 'action' => fn() => 'b'],
        ]);
        $this->assertCount(2, $this->registry->getAll());
    }

    public function test_add_route_with_name(): void
    {
        $this->registry->addRoute('GET', '/hello', fn() => 'hi', RouteRegistryInterface::PRIORITY_NATIVE, [
            'name' => 'greeting',
        ]);
        $this->assertSame('/hello', $this->registry->url('greeting'));
    }

    public function test_url_with_unknown_name_returns_null(): void
    {
        $this->assertNull($this->registry->url('nonexistent'));
    }

    public function test_url_with_parameters(): void
    {
        $this->registry->addRoute('GET', '/posts/{id}', fn() => '', RouteRegistryInterface::PRIORITY_NATIVE, [
            'name' => 'post.show',
        ]);
        $this->assertSame('/posts/42', $this->registry->url('post.show', ['id' => 42]));
    }

    public function test_static_route_match(): void
    {
        $this->registry->addRoute('GET', '/about', fn() => 'About Us');
        $matched = $this->registry->match(self::req('GET', '/about'));
        $this->assertNotNull($matched);
        $this->assertSame('About Us', $matched['action']());
    }

    public function test_dynamic_route_match(): void
    {
        $this->registry->addRoute('GET', '/users/{id}', fn($id) => "User {$id}");
        $matched = $this->registry->match(self::req('GET', '/users/42'));
        $this->assertNotNull($matched);
        $this->assertSame(['id' => '42'], $matched['params']);
    }

    public function test_route_match_returns_null_on_no_match(): void
    {
        $this->assertNull($this->registry->match(self::req('GET', '/nonexistent')));
    }

    public function test_route_match_respects_method(): void
    {
        $this->registry->addRoute('POST', '/submit', fn() => 'submitted');
        $this->assertNull($this->registry->match(self::req('GET', '/submit')));
    }

    public function test_priority_order(): void
    {
        $this->registry->addRoute('GET', '/same', fn() => 'module', RouteRegistryInterface::PRIORITY_MODULE);
        $this->registry->addRoute('GET', '/same', fn() => 'native', RouteRegistryInterface::PRIORITY_NATIVE);
        $matched = $this->registry->match(self::req('GET', '/same'));
        $this->assertNotNull($matched);
        $this->assertSame('module', $matched['action']());
    }

    public function test_dispatch_returns_null_on_no_match(): void
    {
        $this->assertNull($this->registry->dispatch(self::req('GET', '/nope')));
    }

    public function test_dispatch_closure_action(): void
    {
        $this->registry->addRoute('GET', '/ping', fn() => 'pong');
        $response = $this->registry->dispatch(self::req('GET', '/ping'));
        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('pong', $response->getContent());
    }

    public function test_dispatch_named_controller(): void
    {
        $this->registry->addRoute('GET', '/hello', [RouteRegistryTestController::class, 'sayHello']);
        $this->app->bind(RouteRegistryTestController::class);
        $response = $this->registry->dispatch(self::req('GET', '/hello'));
        $this->assertStringContainsString('Hello', $response->getContent());
    }

    public function test_dispatch_invokable_controller(): void
    {
        $this->registry->addRoute('GET', '/invoke', RouteRegistryTestInvokable::class);
        $this->app->bind(RouteRegistryTestInvokable::class);
        $response = $this->registry->dispatch(self::req('GET', '/invoke'));
        $this->assertStringContainsString('Invoked controller', $response->getContent());
    }

    public function test_dispatch_passes_route_params(): void
    {
        $this->registry->addRoute('GET', '/items/{slug}', fn(string $slug) => "Item: {$slug}");
        $response = $this->registry->dispatch(self::req('GET', '/items/hello-world'));
        $this->assertStringContainsString('Item: hello-world', $response->getContent());
    }

    public function test_clear_removes_all_routes(): void
    {
        $this->registry->addRoute('GET', '/test', fn() => 'ok');
        $this->registry->clear();
        $this->assertEmpty($this->registry->getAll());
        $this->assertNull($this->registry->match(self::req('GET', '/test')));
    }

    public function test_reset_clears_routes(): void
    {
        $this->registry->addRoute('GET', '/test', fn() => 'ok');
        $this->registry->reset();
        $this->assertEmpty($this->registry->getAll());
    }

    public function test_middleware_registration(): void
    {
        $this->registry->addMiddleware('SomeMiddleware');
        $m = $this->registry->getMiddleware();
        $this->assertCount(1, $m);
        $this->assertSame('SomeMiddleware', $m[0]['middleware']);
    }

    public function test_middleware_with_only(): void
    {
        $this->registry->addMiddlewareFor('AdminMiddleware', '/admin');
        $m = $this->registry->getMiddleware();
        $this->assertSame(['/admin'], $m[0]['only']);
    }

    public function test_middleware_with_except(): void
    {
        $this->registry->addMiddlewareFor('PublicMiddleware', ['*'], ['/login']);
        $m = $this->registry->getMiddleware();
        $this->assertSame(['/login'], $m[0]['except']);
    }

    public function test_set_middleware(): void
    {
        $this->registry->setMiddleware(['First', 'Second']);
        $this->assertCount(2, $this->registry->getMiddleware());
    }

    public function test_set_middleware_normalizes_strings(): void
    {
        $this->registry->setMiddleware(['First', ['middleware' => 'Second']]);
        $this->assertCount(2, $this->registry->getMiddleware());
    }

    public function test_run_action_with_closure(): void
    {
        $this->registry->addRoute('GET', '/test', fn() => 'result');
        $routes = $this->registry->getAll();
        $response = $this->registry->runAction($routes[0]['action'], self::req());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('result', $response->getContent());
    }

    public function test_run_action_with_raw_response(): void
    {
        $response = $this->registry->runAction(new Response('direct'), Request::createFromGlobals(), []);
        $this->assertStringContainsString('direct', $response->getContent());
    }

    public function test_run_action_with_array_result(): void
    {
        $response = $this->registry->runAction(fn() => ['key' => 'value'], Request::createFromGlobals(), []);
        $this->assertStringContainsString('"key"', $response->getContent());
    }

    public function test_dispatch_optional_param_with_value(): void
    {
        $this->registry->addRoute('GET', '/page/{slug?}', fn(string $slug = 'home') => "Page: {$slug}");
        $response = $this->registry->dispatch(self::req('GET', '/page/about'));
        $this->assertStringContainsString('Page: about', $response->getContent());
    }

    public function test_dispatch_match_priority_fallback(): void
    {
        $this->registry->addRoute('GET', '/fb', fn() => 'fallback', RouteRegistryInterface::PRIORITY_FALLBACK);
        $matched = $this->registry->match(self::req('GET', '/fb'), RouteRegistryInterface::PRIORITY_NATIVE);
        $this->assertNull($matched);
    }
}

class RouteRegistryTestController
{
    public function sayHello(): Response
    {
        return new Response('Hello from controller');
    }
}

class RouteRegistryTestInvokable
{
    public function __invoke(): Response
    {
        return new Response('Invoked controller');
    }
}
