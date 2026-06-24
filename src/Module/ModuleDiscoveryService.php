<?php

declare(strict_types=1);

namespace Witals\Framework\Module;

use Witals\Framework\Application;

class ModuleDiscoveryService
{
    protected array $metadataMap = [];
    protected bool $discovered = false;
    protected array $modulePaths = [];

    public function __construct(
        protected Application $app,
        string $modulesPath = '',
    ) {
        if ($modulesPath === '') {
            $modulesPath = $app->basePath('modules');
        }

        $this->modulePaths = [
            $modulesPath,
            $app->basePath('framework/witals/modules'),
            $app->basePath('framework/presto/modules'),
        ];
    }

    public function addModulePath(string $path): void
    {
        $this->modulePaths[] = $path;
    }

    public function getPaths(): array
    {
        return $this->modulePaths;
    }

    public function discover(): void
    {
        if ($this->discovered) {
            return;
        }

        $this->discovered = true;

        if ($this->loadDiscoveryCache()) {
            return;
        }

        foreach ($this->modulePaths as $modulesPath) {
            if (!is_dir($modulesPath)) {
                continue;
            }

            foreach (scandir($modulesPath) as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $modulePath = $modulesPath . '/' . $entry;

                if (!is_dir($modulePath)) {
                    continue;
                }

                $manifest = new ModuleManifest($modulePath);

                if (!$manifest->valid()) {
                    continue;
                }

                $name = $manifest->name();

                if (isset($this->metadataMap[$name])) {
                    continue;
                }

                $metadata = $manifest->toArray();

                $configKey = "modules.enabled.{$name}";
                if ($this->app->config($configKey) !== null) {
                    $metadata['enabled'] = (bool) $this->app->config($configKey);
                }

                $this->metadataMap[$name] = $metadata;

                $this->discoverFunctions($name, $metadata);
            }
        }

        $this->saveDiscoveryCache();
    }

    public function getMetadataMap(): array
    {
        return $this->metadataMap;
    }

    public function getModuleMetadata(string $name): ?array
    {
        return $this->metadataMap[$name] ?? null;
    }

    public function clearDiscoveryCache(): void
    {
        $cacheFile = $this->discoveryCachePath();

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    public function buildClassMap(): array
    {
        $classmap = [];

        foreach ($this->metadataMap as $meta) {
            $autoload = $meta['autoload']['psr-4'] ?? [];
            $basePath = $meta['_path'] ?? '';

            foreach ($autoload as $ns => $dir) {
                $ns = rtrim($ns, '\\') . '\\';
                $fullDir = $basePath . '/' . ltrim($dir, '/');

                if (!is_dir($fullDir)) {
                    continue;
                }

                $this->scanDirForClassmap($ns, $fullDir, $classmap);
            }
        }

        return $classmap;
    }

    protected function discoveryCachePath(): string
    {
        return $this->app->basePath('storage/framework/cache/modules-discovery.php');
    }

    protected function loadDiscoveryCache(): bool
    {
        $cacheFile = $this->discoveryCachePath();

        if (!file_exists($cacheFile)) {
            return false;
        }

        $cached = require $cacheFile;

        if (!is_array($cached)) {
            return false;
        }

        if (isset($cached['metadata'])) {
            $this->metadataMap = $cached['metadata'];

            $isProduction = $this->app->config('app.env', 'production') === 'production'
                && !$this->app->config('app.debug', false);

            if (!$isProduction) {
                foreach ($cached['_paths'] ?? [] as $path) {
                    if (!is_dir($path)) {
                        return false;
                    }
                }
            }

            return true;
        }

        $this->metadataMap = $cached;

        return true;
    }

    protected function saveDiscoveryCache(): void
    {
        $cacheFile = $this->discoveryCachePath();
        $cacheDir = dirname($cacheFile);

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0775, true);
        }

        $data = [
            'metadata' => $this->metadataMap,
            '_paths' => $this->modulePaths,
            'classmap' => $this->buildClassMap(),
        ];

        $content = '<?php return ' . var_export($data, true) . ';' . "\n";
        file_put_contents($cacheFile, $content, LOCK_EX);
    }

    protected function scanDirForClassmap(string $namespace, string $dir, array &$classmap): void
    {
        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $this->scanDirForClassmap($namespace . $item . '\\', $path, $classmap);
            } elseif (str_ends_with($item, '.php')) {
                $className = $namespace . pathinfo($item, PATHINFO_FILENAME);
                $classmap[$className] = $path;
            }
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
}
