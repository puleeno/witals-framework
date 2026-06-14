<?php

declare(strict_types=1);

namespace App\Http\Routing;

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use App\Http\Routing\Contracts\RouteRegistryInterface;

class RouteRegistry implements RouteRegistryInterface
{
    protected array $routes = [];
    protected array $compiled = [];
    protected array $named = [];
    protected bool $indexed = false;

    protected array $staticIndex = [];
    protected array $dynamicIndex = [];

    protected array $middleware = [];

    protected const PARAM_REGEX = '/\{(\w+)(\??)(?::((?:[^{}]|\{[^{}]*\})+))?\}/';

    public function __construct(
        protected Application $app,
    ) {
        for ($i = 0; $i <= self::PRIORITY_FALLBACK; $i++) {
            $this->routes[$i] = [];
        }
    }

    public function addMiddleware(string $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function setMiddleware(array $middleware): void
    {
        $this->middleware = $middleware;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function addRoute(string $method, string $path, mixed $action, int $priority = self::PRIORITY_NATIVE, array $options = []): void
    {
        $path = '/' . ltrim($path, '/');
        $method = strtoupper($method);
        $name = $options['name'] ?? null;

        $entry = [
            'method' => $method,
            'path' => $path,
            'action' => $action,
            'options' => $options,
            'name' => $name,
            'pattern' => null,
            'params' => $this->parseParams($path),
            'static' => !str_contains($path, '{'),
        ];

        $this->routes[$priority][] = $entry;

        if ($name !== null) {
            $this->named[$name] = ['method' => $method, 'path' => $path, 'options' => $options];
        }

        $this->compiled = [];
        $this->indexed = false;
    }

    public function addRoutes(array $routes, int $priority = self::PRIORITY_NATIVE): void
    {
        foreach ($routes as $route) {
            $this->addRoute(
                method: $route['method'] ?? 'GET',
                path: $route['path'] ?? '/',
                action: $route['action'] ?? null,
                priority: $route['priority'] ?? $priority,
                options: $route['options'] ?? [],
            );
        }
    }

    public function match(Request $request, ?int $maxPriority = null, ?string $path = null): ?array
    {
        $method = strtoupper($request->method());
        $path = $path ?? '/' . ltrim($request->path(), '/');
        $maxP = $maxPriority ?? self::PRIORITY_FALLBACK;

        $this->ensureIndexed();

        for ($p = 0; $p <= $maxP; $p++) {
            if (isset($this->staticIndex[$p][$method][$path])) {
                return $this->buildMatch($this->staticIndex[$p][$method][$path], []);
            }

            if (!isset($this->dynamicIndex[$p][$method])) {
                continue;
            }

            foreach ($this->dynamicIndex[$p][$method] as $group) {
                if (!str_starts_with($path, $group['prefix'])) {
                    continue;
                }
                if (preg_match($group['regex'], $path, $matches)) {
                    return $this->buildMatch(
                        $group['route'],
                        $this->extractParams($group['paramNames'], $matches)
                    );
                }
            }
        }

        return null;
    }

    public function dispatch(Request $request): ?Response
    {
        $matched = $this->match($request);

        if ($matched === null) {
            return null;
        }

        if ($this->middleware === []) {
            return $this->runAction($matched['action'], $request, $matched['params']);
        }

        $destination = function (Request $request) use ($matched) {
            return $this->runAction($matched['action'], $request, $matched['params']);
        };

        return $this->runMiddlewarePipeline($request, $this->middleware, $destination);
    }

    protected function runMiddlewarePipeline(Request $request, array $pipeline, callable $destination): Response
    {
        $middleware = array_shift($pipeline);

        if ($middleware === null) {
            return $destination($request);
        }

        $next = function (Request $nextRequest) use ($pipeline, $destination) {
            return $this->runMiddlewarePipeline($nextRequest, $pipeline, $destination);
        };

        if ($middleware instanceof \Closure) {
            return $middleware($request, $next);
        }

        if (is_string($middleware)) {
            $instance = $this->app->make($middleware);
            if (method_exists($instance, 'handle')) {
                return $instance->handle($request, $next);
            }
        }

        throw new \RuntimeException("Invalid middleware: " . json_encode($middleware));
    }

    public function getAll(): array
    {
        $all = [];
        for ($p = 0; $p <= self::PRIORITY_FALLBACK; $p++) {
            if (isset($this->routes[$p])) {
                foreach ($this->routes[$p] as $route) {
                    $all[] = $route;
                }
            }
        }
        return $all;
    }

    public function clear(): void
    {
        for ($i = 0; $i <= self::PRIORITY_FALLBACK; $i++) {
            $this->routes[$i] = [];
        }
        $this->compiled = [];
        $this->named = [];
        $this->staticIndex = [];
        $this->dynamicIndex = [];
        $this->indexed = false;
    }

    public function runAction(mixed $action, Request $request, array $params = []): Response
    {
        if ($action instanceof \Closure) {
            $result = $this->app->call($action, array_merge(['request' => $request], $params));
            return $this->toResponse($result);
        }

        if (is_array($action) && count($action) === 2) {
            [$controller, $method] = $action;
            if (is_string($controller)) {
                $controller = $this->app->make($controller);
            }
            $result = $this->app->call([$controller, $method], array_merge(['request' => $request], $params));
            return $this->toResponse($result);
        }

        if (is_string($action) && class_exists($action)) {
            $instance = $this->app->make($action);
            if (method_exists($instance, '__invoke')) {
                $result = $this->app->call([$instance, '__invoke'], array_merge(['request' => $request], $params));
                return $this->toResponse($result);
            }
        }

        if ($action instanceof Response) {
            return $action;
        }

        return $this->toResponse($action);
    }

    public function url(string $name, array $params = []): ?string
    {
        if (!isset($this->named[$name])) {
            return null;
        }

        $path = $this->named[$name]['path'];

        foreach ($params as $key => $value) {
            $path = str_replace(
                ['{' . $key . '}', '{' . $key . '?}', '{' . $key . '?::' . $this->extractConstraint($path, $key) . '}'],
                (string) $value,
                $path
            );
        }

        $path = preg_replace('/\{(\w+)\??(?::(?:[^{}]|\{[^{}]*\})+)?\}/', '', $path);
        $path = preg_replace('#/+#', '/', $path);
        $path = rtrim($path, '/') ?: '/';

        return $path;
    }

    protected function ensureIndexed(): void
    {
        if ($this->indexed) {
            return;
        }

        $this->staticIndex = [];
        $this->dynamicIndex = [];

        for ($p = 0; $p <= self::PRIORITY_FALLBACK; $p++) {
            foreach ($this->routes[$p] ?? [] as $route) {
                $m = $route['method'];

                if ($route['static']) {
                    $this->staticIndex[$p][$m][$route['path']] = $route;
                } else {
                    $this->dynamicIndex[$p][$m][] = [
                        'prefix' => $this->extractPrefix($route['path']),
                        'regex' => $this->compile($route['path']),
                        'route' => $route,
                        'paramNames' => $route['params'],
                    ];
                }
            }

            foreach ($this->dynamicIndex[$p] ?? [] as $m => &$groups) {
                usort($groups, fn($a, $b) => strlen($b['prefix']) <=> strlen($a['prefix']));
            }
        }

        $this->indexed = true;
    }

    protected function extractPrefix(string $path): string
    {
        $first = strpos($path, '{');
        if ($first === false) {
            return $path;
        }
        $lastSlash = strrpos(substr($path, 0, $first), '/');
        if ($lastSlash === false) {
            return '';
        }
        return substr($path, 0, $lastSlash + 1);
    }

    protected function extractParams(array $paramNames, array $matches): array
    {
        $params = [];
        foreach ($paramNames as $name) {
            if (isset($matches[$name]) && $matches[$name] !== '') {
                $params[$name] = $matches[$name];
            }
        }
        return $params;
    }

    protected function extractConstraint(string $path, string $name): string
    {
        preg_match('/\{' . $name . '\??(?::((?:[^{}]|\{[^{}]*\})+))?\}/', $path, $m);
        return $m[1] ?? '';
    }

    protected function compile(string $path): string
    {
        if (isset($this->compiled[$path])) {
            return $this->compiled[$path];
        }

        $pattern = preg_replace_callback('/\/\{(\w+)\?(?::((?:[^{}]|\{[^{}]*\})+))?\}/', function ($m) {
            $name = $m[1];
            $regex = !empty($m[2]) ? $m[2] : '[^/]+';
            return '(/(?P<' . $name . '>' . $regex . '))?';
        }, $path);

        $pattern = preg_replace_callback('/\{(\w+)(?::((?:[^{}]|\{[^{}]*\})+))?\}/', function ($m) {
            $name = $m[1];
            $regex = !empty($m[2]) ? $m[2] : '[^/]+';
            return '(?P<' . $name . '>' . $regex . ')';
        }, $pattern);

        $pattern = '#^' . $pattern . '$#i';
        $this->compiled[$path] = $pattern;

        return $pattern;
    }

    protected function parseParams(string $path): array
    {
        preg_match_all(self::PARAM_REGEX, $path, $m);
        return array_map(fn($n) => rtrim($n, '?'), $m[1]);
    }

    protected function buildMatch(array $route, array $params): array
    {
        return [
            'action' => $route['action'],
            'params' => $params,
            'priority' => $this->findPriority($route),
            'options' => $route['options'],
            'name' => $route['name'] ?? null,
        ];
    }

    protected function findPriority(array $route): int
    {
        for ($p = 0; $p <= self::PRIORITY_FALLBACK; $p++) {
            if (isset($this->routes[$p]) && in_array($route, $this->routes[$p], true)) {
                return $p;
            }
        }
        return self::PRIORITY_NATIVE;
    }

    protected function toResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_string($result)) {
            return Response::html($result);
        }

        if (is_array($result) || is_object($result)) {
            return Response::json($result);
        }

        return Response::html((string) $result);
    }
}
