<?php

declare(strict_types=1);

namespace Witals\Framework\Module;

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use RuntimeException;

class ModuleManager implements Contracts\ModuleManagerInterface
{
    protected array $metadataMap = [];

    protected array $routeIndex = [];

    protected array $loaded = [];

    protected array $instances = [];

    protected bool $discovered = false;

    public function __construct(
        protected Application $app,
        protected string $modulesPath = '',
    ) {
        if ($modulesPath === '') {
            $this->modulesPath = $app->basePath('modules');
        }
    }

    public function discover(): void
    {
        if ($this->discovered) {
            return;
        }

        $this->discovered = true;

        if (!is_dir($this->modulesPath)) {
            return;
        }

        foreach (scandir($this->modulesPath) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $modulePath = $this->modulesPath . '/' . $entry;
            $metaFile = $modulePath . '/module.json';

            if (!is_file($metaFile)) {
                continue;
            }

            $metadata = json_decode(file_get_contents($metaFile), true);

            if ($metadata === null || !isset($metadata['name'])) {
                continue;
            }

            $name = $metadata['name'];

            $configKey = "modules.enabled.{$name}";
            if ($this->app->config($configKey) !== null) {
                $metadata['enabled'] = (bool) $this->app->config($configKey);
            }

            $metadata['_type'] = $metadata['type'] ?? 'support';
            $metadata['_path'] = $modulePath;

            $this->metadataMap[$name] = $metadata;
        }
    }

    public function buildRouteIndex(): array
    {
        if ($this->routeIndex !== []) {
            return $this->routeIndex;
        }

        $this->discover();

        $index = [];

        foreach ($this->metadataMap as $name => $meta) {
            if (!($meta['enabled'] ?? false)) {
                continue;
            }

            if (($meta['_type'] ?? 'support') !== 'route') {
                continue;
            }

            $prefix = $meta['route_prefix'] ?? '';
            $routes = $meta['routes'] ?? [];

            $routePrefix = $prefix !== '' ? '/' . ltrim($prefix, '/') : '';

            foreach ($routes as $route) {
                $method = strtoupper($route['method'] ?? 'GET');
                $path = $route['path'] ?? '';

                $fullPath = $routePrefix . '/' . ltrim($path, '/');
                $pattern = $this->pathToRegex($fullPath);

                $index[] = [
                    'method' => $method,
                    'pattern' => $pattern,
                    'module' => $name,
                    'handler' => $route['handler'] ?? null,
                ];
            }
        }

        usort($index, fn ($a, $b) => strlen($b['pattern']) <=> strlen($a['pattern']));

        $this->routeIndex = $index;

        return $this->routeIndex;
    }

    public function matchRoute(string $method, string $path): ?string
    {
        $index = $this->buildRouteIndex();

        foreach ($index as $entry) {
            if ($entry['method'] !== $method) {
                continue;
            }

            if (preg_match($entry['pattern'], $path)) {
                return $entry['module'];
            }
        }

        return null;
    }

    public function resolveModuleForRequest(Request $request): ?ModuleInterface
    {
        $method = $request->method();
        $path = '/' . ltrim($request->path(), '/');

        $index = $this->buildRouteIndex();

        foreach ($index as $entry) {
            if ($entry['method'] !== $method) {
                continue;
            }

            if (!preg_match($entry['pattern'], $path, $matches)) {
                continue;
            }

            $module = $this->load($entry['module']);

            if ($module === null) {
                continue;
            }

            return $module;
        }

        return null;
    }

    public function dispatch(Request $request): ?Response
    {
        $method = $request->method();
        $path = '/' . ltrim($request->path(), '/');

        $index = $this->buildRouteIndex();

        foreach ($index as $entry) {
            if ($entry['method'] !== $method) {
                continue;
            }

            if (!preg_match($entry['pattern'], $path, $matches)) {
                continue;
            }

            $module = $this->load($entry['module']);

            if ($module === null) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            return $this->executeHandler($entry['handler'], $request, $params);
        }

        return null;
    }

    protected function executeHandler(mixed $handler, Request $request, array $params): Response
    {
        $result = match (true) {
            $handler instanceof \Closure => $this->app->call($handler, array_merge(['request' => $request], $params)),
            is_string($handler) => $this->app->call($handler, array_merge(['request' => $request], $params)),
            is_array($handler) => $this->executeController($handler, $request, $params),
            default => $handler,
        };

        return $this->normalizeResponse($result);
    }

    protected function executeController(array $handler, Request $request, array $params): mixed
    {
        [$class, $method] = $handler;
        $instance = is_string($class) ? $this->app->make($class) : $class;

        return $this->app->call([$instance, $method], array_merge(['request' => $request], $params));
    }

    protected function normalizeResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_string($result)) {
            return Response::html($result);
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::json(['error' => 'Invalid module response'], 500);
    }

    public function load(string $name): ?ModuleInterface
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        if (!isset($this->metadataMap[$name])) {
            return null;
        }

        $meta = $this->metadataMap[$name];

        $instance = new Module($this->app, $meta['_path'], $meta);

        $instance->register();

        // Load dependencies before booting
        $this->loadDependencies($instance);

        $instance->boot();

        $this->instances[$name] = $instance;
        $this->loaded[$name] = true;

        return $instance;
    }

    protected function loadDependencies(Module $module): void
    {
        foreach ($module->getDependencies() as $dep) {
            if (!isset($this->loaded[$dep])) {
                $this->load($dep);
            }
        }
    }

    public function loadSupportModules(): void
    {
        $this->discover();

        $sorted = $this->sortByPriority();

        foreach ($sorted as $name => $meta) {
            if (!($meta['enabled'] ?? false)) {
                continue;
            }

            $type = $meta['_type'] ?? 'support';

            if ($type !== 'support') {
                continue;
            }

            $this->load($name);
        }
    }

    public function isLoaded(string $name): bool
    {
        return isset($this->loaded[$name]);
    }

    public function all(): array
    {
        $this->discover();

        return $this->metadataMap;
    }

    public function getLoaded(): array
    {
        return $this->instances;
    }

    protected function sortByPriority(): array
    {
        $modules = $this->metadataMap;

        uasort($modules, function (array $a, array $b) {
            $pa = $a['priority'] ?? 50;
            $pb = $b['priority'] ?? 50;

            if ($pa !== $pb) {
                return $pa <=> $pb;
            }

            return ($a['name'] ?? '') <=> ($b['name'] ?? '');
        });

        return $modules;
    }

    protected function pathToRegex(string $path): string
    {
        $path = '/' . ltrim($path, '/');

        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);

        return '#^' . $pattern . '$#';
    }
}
