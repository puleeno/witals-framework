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
                    'module' => $name,
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

            if (($meta['_type'] ?? 'support') !== 'support') {
                continue;
            }

            $this->load($name);
        }
    }

    protected function loadDependencies(Module $module): void
    {
        foreach ($module->getDependencies() as $dep) {
            if (isset($this->loaded[$dep])) {
                continue;
            }

            if (!isset($this->metadataMap[$dep])) {
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
