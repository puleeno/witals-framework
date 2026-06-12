<?php

declare(strict_types=1);

namespace Witals\Framework\Module;

use Witals\Framework\Application;
use Witals\Framework\Module\Contracts\ModuleInterface;

class Module implements ModuleInterface
{
    protected bool $booted = false;

    protected bool $registered = false;

    protected array $functions = [];

    protected array $flattenedFunctions = [];

    protected ?ModuleManifest $manifest = null;

    public function __construct(
        protected Application $app,
        protected string $path,
        protected array $metadata = [],
    ) {
        if (empty($metadata) && ModuleManifest::exists($path)) {
            $this->manifest = new ModuleManifest($path);
            $this->metadata = $this->manifest->toArray();
        }

        $this->buildFunctionTree();
    }

    public function manifest(): ?ModuleManifest
    {
        return $this->manifest;
    }

    protected function buildFunctionTree(): void
    {
        $raw = $this->metadata['functions'] ?? [];

        foreach ($raw as $name => $cfg) {
            $this->functions[$name] = $this->buildFunctionNode($name, $cfg);
        }

        $this->flattenedFunctions = [];
        foreach ($this->functions as $fn) {
            $this->flattenedFunctions += $fn->flatten();
        }
    }

    protected function buildFunctionNode(string $name, array $cfg, ?ModuleFunction $parent = null): ModuleFunction
    {
        $node = new ModuleFunction($this->getName(), $name, $cfg, $parent);

        $children = $cfg['functions'] ?? [];

        foreach ($children as $cName => $cCfg) {
            $node->addChild($this->buildFunctionNode($cName, $cCfg, $node));
        }

        return $node;
    }

    public function getName(): string
    {
        return $this->metadata['name'] ?? 'unknown';
    }

    public function getVersion(): string
    {
        return $this->metadata['version'] ?? '1.0.0';
    }

    public function getDescription(): string
    {
        return $this->metadata['description'] ?? '';
    }

    public function getType(): string
    {
        return $this->metadata['type'] ?? 'support';
    }

    public function getPriority(): int
    {
        return (int) ($this->metadata['priority'] ?? 50);
    }

    public function getDependencies(): array
    {
        $deps = $this->metadata['dependencies'] ?? [];

        return is_array($deps) ? array_keys($deps) : $deps;
    }

    public function getDependencyNames(): array
    {
        return $this->getDependencies();
    }

    public function getProvides(): array
    {
        return $this->metadata['provides'] ?? [];
    }

    public function getConsumes(): array
    {
        return $this->metadata['consumes'] ?? [];
    }

    public function getRoutePrefix(): string
    {
        return $this->metadata['route_prefix'] ?? '';
    }

    public function getRoutes(): array
    {
        return $this->metadata['routes'] ?? [];
    }

    public function isEnabled(): bool
    {
        return $this->metadata['enabled'] ?? false;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    public function hasFunction(string $name): bool
    {
        return isset($this->flattenedFunctions[$this->getName() . '.' . $name]);
    }

    public function getFunction(string $name): ?ModuleFunction
    {
        return $this->flattenedFunctions[$this->getName() . '.' . $name] ?? null;
    }

    public function getAllFunctionMetadata(): array
    {
        return $this->flattenedFunctions;
    }

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;

        foreach ($this->getProviders() as $provider) {
            if (is_string($provider)) {
                $this->app->register($provider);
            }
        }

        foreach ($this->flattenedFunctions as $fnMeta) {
            if (!($fnMeta['enabled'] ?? true)) {
                continue;
            }

            foreach ($fnMeta['providers'] ?? [] as $provider) {
                if (is_string($provider)) {
                    $this->app->register($provider);
                }
            }
        }
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        $bootstrap = $this->metadata['bootstrap'] ?? null;

        if ($bootstrap !== null && is_string($bootstrap) && class_exists($bootstrap)) {
            $instance = $this->app->make($bootstrap);
            if (method_exists($instance, 'boot')) {
                $instance->boot();
            }
        }

        foreach ($this->flattenedFunctions as $fnMeta) {
            if (!($fnMeta['enabled'] ?? true)) {
                continue;
            }

            $bs = $fnMeta['bootstrap'] ?? null;

            if ($bs !== null && is_string($bs) && class_exists($bs)) {
                $instance = $this->app->make($bs);
                if (method_exists($instance, 'boot')) {
                    $instance->boot();
                }
            }
        }
    }

    protected function getProviders(): array
    {
        return $this->metadata['providers'] ?? [];
    }
}
