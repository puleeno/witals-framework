<?php

declare(strict_types=1);

namespace Witals\Framework\Module;

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Module\Contracts\ModuleInterface;

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

            $raw = file_get_contents($metaFile);
            $metadata = json_decode($raw, true);

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

            $this->discoverFunctions($name, $metadata);
        }
    }

    protected function discoverFunctions(string $moduleName, array &$metadata): void
    {
        $raw = $metadata['functions'] ?? [];

        $this->flattenFunctions($moduleName, $raw, $moduleName, $metadata);
    }

    protected function flattenFunctions(
        string $moduleName,
        array $functions,
        string $prefix,
        array &$moduleMeta,
        array $parentChain = [],
    ): void {
        foreach ($functions as $fnName => $fnCfg) {
            $fullFnName = $prefix . '.' . $fnName;
            $chain = array_merge($parentChain, [$fnName]);

            $fnType = $fnCfg['type'] ?? 'support';
            $fnEnabled = $fnCfg['enabled'] ?? ($moduleMeta['enabled'] ?? true);
            $fnPriority = $fnCfg['priority'] ?? ($moduleMeta['priority'] ?? 50);
            $fnPrefix = $fnCfg['route_prefix'] ?? '';

            $children = $fnCfg['functions'] ?? [];
            unset($fnCfg['functions']);

            $entry = $fnCfg;
            $entry['name'] = $fullFnName;
            $entry['type'] = $fnType;
            $entry['enabled'] = $fnEnabled;
            $entry['priority'] = $fnPriority;
            $entry['_function'] = true;
            $entry['_module'] = $moduleName;
            $entry['_chain'] = $chain;
            $entry['_path'] = $moduleMeta['_path'];
            $entry['_parent'] = $parentChain !== [] ? $prefix : null;
            $entry['route_prefix'] = $fnPrefix;
            $entry['routes'] = $fnCfg['routes'] ?? [];

            $this->metadataMap[$fullFnName] = $entry;

            if ($children !== []) {
                $this->flattenFunctions($moduleName, $children, $fullFnName, $moduleMeta, $chain);
            }
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

            $effectiveType = $meta['_type'] ?? $meta['type'] ?? 'support';
            if ($effectiveType !== 'route' && !($meta['_function'] ?? false)) {
                continue;
            }

            if (($meta['_function'] ?? false) && $meta['type'] !== 'route') {
                continue;
            }

            $moduleName = $meta['_module'] ?? $name;
            $moduleMeta = $this->metadataMap[$moduleName] ?? [];
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

                $index[] = [
                    'method' => $method,
                    'pattern' => $pattern,
                    'module' => $moduleName,
                    'function' => $meta['_function'] ?? false ? $name : null,
                    'handler' => $route['handler'],
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

            if ($entry['function'] !== null) {
                $fn = $module->getFunction(
                    implode('.', array_slice(explode('.', $entry['function']), 1))
                );

                if ($fn === null || !$fn->isEnabled()) {
                    continue;
                }
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            return $this->executeHandler($entry['handler'], $request, $params);
        }

        return null;
    }

    public function load(string $name): ?ModuleInterface
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        $this->discover();

        $isFunction = str_contains($name, '.');
        $moduleName = $isFunction ? explode('.', $name)[0] : $name;

        if (!isset($this->metadataMap[$moduleName])) {
            return null;
        }

        if ($isFunction) {
            $module = $this->loadModule($moduleName);

            if ($module === null) {
                return null;
            }

            $fn = $module->getFunction(
                implode('.', array_slice(explode('.', $name), 1))
            );

            if ($fn === null) {
                return null;
            }

            $this->loaded[$name] = true;

            return $module;
        }

        return $this->loadModule($name);
    }

    protected function loadModule(string $name): ?Module
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        if (!isset($this->metadataMap[$name])) {
            return null;
        }

        $meta = $this->metadataMap[$name];

        $instance = new Module($this->app, $meta['_path'], $meta);

        try {
            $instance->register();
            $this->loadDependencies($instance);
            $instance->boot();
        } catch (\Throwable) {
            return null;
        }

        $this->instances[$name] = $instance;
        $this->loaded[$name] = true;

        return $instance;
    }

    public function loadSupportModules(): void
    {
        $this->discover();

        $sorted = $this->sortByPriority();

        foreach ($sorted as $name => $meta) {
            if (!($meta['enabled'] ?? false)) {
                continue;
            }

            if (str_contains($name, '.')) {
                continue;
            }

            $effectiveType = $meta['_type'] ?? $meta['type'] ?? 'support';
            if ($effectiveType !== 'support') {
                continue;
            }

            $this->load($name);
        }
    }

    public function loadFunction(string $fullName): ?ModuleInterface
    {
        return $this->load($fullName);
    }

    protected function loadDependencies(Module $module): void
    {
        foreach ($module->getDependencies() as $dep) {
            if (isset($this->loaded[$dep])) {
                continue;
            }

            if ($dep !== '' && !isset($this->metadataMap[$dep])) {
                continue;
            }

            $this->load($dep);
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
        $modules = [];

        foreach ($this->metadataMap as $name => $meta) {
            if (!str_contains($name, '.')) {
                $modules[$name] = $meta;
            }
        }

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
