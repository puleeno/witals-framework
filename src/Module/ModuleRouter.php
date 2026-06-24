<?php

declare(strict_types=1);

namespace Witals\Framework\Module;

use Witals\Framework\Application;
use Psr\Log\LoggerInterface;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use App\Http\Routing\Contracts\RouteRegistryInterface;

class ModuleRouter
{
    protected array $routeIndex = [];

    protected ?\Closure $moduleLoader = null;

    public function __construct(
        protected Application $app,
        protected ModuleDiscoveryService $discoveryService,
    ) {}

    public function setModuleLoader(\Closure $loader): void
    {
        $this->moduleLoader = $loader;
    }

    public function buildRouteIndex(): array
    {
        if ($this->routeIndex !== []) {
            return $this->routeIndex;
        }

        $this->discoveryService->discover();

        $metadataMap = $this->discoveryService->getMetadataMap();
        $index = [];

        foreach ($metadataMap as $name => $meta) {
            if (!($meta['enabled'] ?? false)) {
                continue;
            }

            $effectiveType = $meta['_type'] ?? $meta['type'] ?? 'support';
            if ($effectiveType !== 'route' && !($meta['_function'] ?? false)) {
                continue;
            }

            if (($meta['_function'] ?? false) && $meta['type'] !== 'route') {
                continue;
            }

            $moduleName = $meta['_module'] ?? $name;
            $moduleMeta = $metadataMap[$moduleName] ?? [];
            $modulePrefix = $moduleMeta['route_prefix'] ?? '';

            $fnPrefix = ($meta['_function'] ?? false) ? ($meta['route_prefix'] ?? '') : '';

            $prefix = $modulePrefix;
            if ($fnPrefix !== '') {
                $prefix = $prefix !== '' ? $prefix . '/' . ltrim($fnPrefix, '/') : $fnPrefix;
            }

            $routePrefix = $prefix !== '' ? '/' . ltrim($prefix, '/') : '';

            $routes = $meta['routes'] ?? [];

            foreach ($routes as $route) {
                if (!isset($route['method'], $route['path'], $route['handler'])) {
                    continue;
                }

                $method = strtoupper($route['method']);
                $path = $route['path'];

                $fullPath = $routePrefix . '/' . ltrim($path, '/');
                $pattern = $this->pathToRegex($fullPath);

                $index[$method][] = [
                    'method' => $method,
                    'pattern' => $pattern,
                    'module' => $moduleName,
                    'function' => $meta['_function'] ?? false ? $name : null,
                    'handler' => $route['handler'],
                ];
            }
        }

        foreach ($index as $method => &$entries) {
            usort($entries, fn($a, $b) => strlen($b['pattern']) <=> strlen($a['pattern']));
        }
        unset($entries);

        $this->routeIndex = $index;

        return $this->routeIndex;
    }

    public function matchRoute(string $method, string $path): ?string
    {
        $index = $this->buildRouteIndex();
        $method = strtoupper($method);

        $entries = $index[$method] ?? [];

        foreach ($entries as $entry) {
            if (preg_match($entry['pattern'], $path)) {
                return $entry['module'];
            }
        }

        return null;
    }

    public function registerModuleRoutes(RouteRegistryInterface $registry): void
    {
        $this->discoveryService->discover();

        $metadataMap = $this->discoveryService->getMetadataMap();

        foreach ($metadataMap as $name => $meta) {
            if (!($meta['enabled'] ?? false)) {
                continue;
            }

            $routes = $meta['routes'] ?? [];
            if ($routes === []) {
                continue;
            }

            $modulePrefix = $meta['route_prefix'] ?? '';
            $routePrefix = $modulePrefix !== '' ? '/' . ltrim($modulePrefix, '/') : '';

            foreach ($routes as $route) {
                if (!isset($route['method'], $route['path'], $route['handler'])) {
                    continue;
                }

                $handler = $route['handler'];
                $validationError = $this->validateHandler($handler, $name);
                if ($validationError !== null) {
                    $this->app->make(\Psr\Log\LoggerInterface::class)->warning(
                        'Module route skipped: {error}',
                        ['error' => $validationError, 'module' => $name]
                    );
                    continue;
                }

                $method = strtoupper($route['method']);
                $path = $routePrefix . '/' . ltrim($route['path'], '/');

                $registry->addRoute(
                    method: $method,
                    path: $path,
                    action: $this->wrapModuleHandler($name, $handler),
                    priority: RouteRegistryInterface::PRIORITY_MODULE,
                    options: ['module' => $name, 'function' => $meta['_function'] ?? null],
                );
            }
        }
    }

    public function dispatch(Request $request): ?Response
    {
        $method = strtoupper($request->method());
        $path = '/' . ltrim($request->path(), '/');

        $index = $this->buildRouteIndex();
        $entries = $index[$method] ?? [];

        foreach ($entries as $entry) {
            if (!preg_match($entry['pattern'], $path, $matches)) {
                continue;
            }

            $module = $this->moduleLoader !== null
                ? ($this->moduleLoader)($entry['module'])
                : null;

            if ($module === null) {
                continue;
            }

            if ($entry['function'] !== null) {
                $parts = explode('.', $entry['function']);
                $fn = $module->getFunction(implode('.', array_slice($parts, 1)));

                if ($fn === null || !$fn->isEnabled()) {
                    continue;
                }
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            return $this->executeHandler($entry['handler'], $request, $params);
        }

        return null;
    }

    protected function validateHandler(mixed $handler, string $moduleName): ?string
    {
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            if (!class_exists($class)) {
                return "Handler class '{$class}' not found (module: {$moduleName})";
            }
            if (!method_exists($class, $method)) {
                return "Handler method '{$method}()' not found on '{$class}' (module: {$moduleName})";
            }
            return null;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            if (!class_exists($class)) {
                return "Handler class '{$class}' not found (module: {$moduleName})";
            }
            if (!method_exists($class, $method)) {
                return "Handler method '{$method}()' not found on '{$class}' (module: {$moduleName})";
            }
            return null;
        }

        return "Invalid handler format (module: {$moduleName})";
    }

    protected function pathToRegex(string $path): string
    {
        $path = '/' . ltrim($path, '/');

        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);

        return '#^' . $pattern . '$#';
    }

    protected function wrapModuleHandler(string $moduleName, array $handler): \Closure
    {
        return function (Request $request) use ($moduleName, $handler) {
            return $this->executeHandler($handler, $request, []);
        };
    }

    protected function executeHandler(array $handler, Request $request, array $params): Response
    {
        [$class, $method] = $handler;

        $instance = $this->app->make($class);

        $result = $this->app->call([$instance, $method], $params + ['request' => $request]);

        if ($result instanceof Response) {
            return $result;
        }

        if (is_string($result)) {
            return new Response($result, 200);
        }

        if (is_array($result)) {
            return new Response(json_encode($result), 200, ['Content-Type' => 'application/json']);
        }

        return new Response('', 204);
    }
}
