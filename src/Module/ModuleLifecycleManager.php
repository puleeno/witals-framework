<?php

declare(strict_types=1);

namespace Witals\Framework\Module;

use Witals\Framework\Application;
use Psr\Log\LoggerInterface;
use Witals\Framework\Module\Contracts\ModuleInterface;

class ModuleLifecycleManager
{
    protected array $loaded = [];

    protected array $instances = [];

    protected array $loading = [];

    protected static ?array $moduleAutoloadMap = null;

    protected static array $moduleClassmap = [];

    protected static bool $autoloadersRegistered = false;

    public function __construct(
        protected Application $app,
        protected ModuleDiscoveryService $discoveryService,
        protected LoggerInterface $logger,
    ) {}

    public function load(string $name): ?ModuleInterface
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        $parts = explode('.', $name);
        $isFunction = count($parts) > 1;
        $moduleName = $isFunction ? $parts[0] : $name;

        $metadata = $this->discoveryService->getModuleMetadata($moduleName);
        if ($metadata === null) {
            return null;
        }

        if ($isFunction) {
            $module = $this->loadModule($moduleName, $metadata);
            if ($module === null) {
                return null;
            }
            $fn = $module->getFunction(implode('.', array_slice($parts, 1)));
            if ($fn === null) {
                return null;
            }
            $this->loaded[$name] = true;
            return $module;
        }

        return $this->loadModule($name, $metadata);
    }

    protected function loadModule(string $name, array $meta): ?Module
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        if (isset($this->loading[$name])) {
            $chain = implode(' -> ', array_keys($this->loading)) . ' -> ' . $name;
            throw Exceptions\ModuleException::circularDependency($name, $chain);
        }

        $this->loading[$name] = true;

        try {
            $path = $meta['_path'];
            $entryClass = $this->entryClassFromMeta($meta);

            $instance = $entryClass !== null && class_exists($entryClass)
                ? new $entryClass($this->app, $path, $meta)
                : new Module($this->app, $path, $meta);

            if (!$instance instanceof Module) {
                unset($this->loading[$name]);
                return null;
            }

            $instance->register();
            $this->loadDependencies($instance);
            $this->validateVersionConstraints($name, $meta);
            $instance->boot();

            if ($entryClass !== null && !$this->app->has($entryClass)) {
                $this->app->instance($entryClass, $instance);
            }

            $this->instances[$name] = $instance;
            $this->loaded[$name] = true;

            unset($this->loading[$name]);

            return $instance;
        } catch (\Throwable $e) {
            unset($this->loading[$name]);
            $this->logger->error(
                'Failed to load module {name}: {message}',
                ['name' => $name, 'message' => $e->getMessage(), 'exception' => $e]
            );
            return null;
        }
    }

    protected function entryClassFromMeta(array $meta): ?string
    {
        $ns = $meta['namespace'] ?? '';
        $entry = $meta['entry'] ?? '';
        if ($ns === '' || $entry === '') {
            return null;
        }
        return $ns . '\\' . pathinfo($entry, PATHINFO_FILENAME);
    }

    public function loadSupportModules(): void
    {
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

        $this->registerModuleAutoloaders();
    }

    protected function loadDependencies(Module $module): void
    {
        foreach ($module->getDependencyNames() as $dep) {
            if (isset($this->loaded[$dep])) {
                continue;
            }
            if ($dep !== '' && $this->discoveryService->getModuleMetadata($dep) === null) {
                continue;
            }
            $this->load($dep);
        }
    }

    protected function validateVersionConstraints(string $name, array $meta): void
    {
        $deps = $meta['dependencies'] ?? [];
        if (!is_array($deps) || array_is_list($deps)) {
            return;
        }

        foreach ($deps as $depName => $constraint) {
            if (!isset($this->instances[$depName])) {
                continue;
            }
            $depVersion = $this->instances[$depName]->getVersion();
            if (!VersionConstraint::satisfies($depVersion, (string) $constraint)) {
                throw Exceptions\ModuleException::versionMismatch($name, $depName, (string) $constraint, $depVersion);
            }
        }
    }

    public function isLoaded(string $name): bool
    {
        return isset($this->loaded[$name]);
    }

    public function getLoaded(): array
    {
        return $this->instances;
    }

    public function loadFunction(string $fullName): ?ModuleInterface
    {
        return $this->load($fullName);
    }

    protected function sortByPriority(): array
    {
        $modules = [];

        foreach ($this->discoveryService->getMetadataMap() as $name => $meta) {
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

    protected function registerModuleAutoloaders(): void
    {
        if (self::$autoloadersRegistered) {
            return;
        }

        self::$autoloadersRegistered = true;

        $prefixes = [];

        foreach ($this->discoveryService->getMetadataMap() as $meta) {
            $autoload = $meta['autoload']['psr-4'] ?? [];
            $basePath = $meta['_path'] ?? '';

            foreach ($autoload as $ns => $dir) {
                $ns = rtrim($ns, '\\') . '\\';
                $fullPath = $basePath . '/' . ltrim($dir, '/');
                if (!isset($prefixes[$ns])) {
                    $prefixes[$ns] = [];
                }
                $prefixes[$ns][] = rtrim($fullPath, '/');
            }
        }

        if ($prefixes === []) {
            return;
        }

        self::$moduleAutoloadMap = $prefixes;

        spl_autoload_register(function (string $class): void {
            if (isset(self::$moduleClassmap[$class])) {
                require self::$moduleClassmap[$class];
                return;
            }

            foreach (self::$moduleAutoloadMap as $prefix => $dirs) {
                if (str_starts_with($class, $prefix)) {
                    $relativeClass = substr($class, strlen($prefix));
                    $fileRelative = str_replace('\\', '/', $relativeClass) . '.php';
                    foreach ($dirs as $dir) {
                        $file = $dir . '/' . $fileRelative;
                        if (file_exists($file)) {
                            require $file;
                            return;
                        }
                    }
                }
            }
        });
    }

    public function reset(): void
    {
        $this->loaded = [];
        $this->instances = [];
        $this->loading = [];
        self::$moduleClassmap = [];
        self::$moduleAutoloadMap = null;
        self::$autoloadersRegistered = false;
    }
}
