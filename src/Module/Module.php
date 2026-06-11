<?php

declare(strict_types=1);

namespace Witals\Framework\Module;

use Witals\Framework\Application;
use Witals\Framework\Module\Contracts\ModuleInterface;

class Module implements ModuleInterface
{
    protected bool $booted = false;

    protected bool $registered = false;

    public function __construct(
        protected Application $app,
        protected string $path,
        protected array $metadata = [],
    ) {
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
        return $this->metadata['priority'] ?? 50;
    }

    public function getDependencies(): array
    {
        return $this->metadata['depends'] ?? [];
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
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        $this->registerAutoload();

        $bootstrap = $this->metadata['bootstrap'] ?? null;

        if ($bootstrap !== null && is_string($bootstrap) && class_exists($bootstrap)) {
            $instance = $this->app->make($bootstrap);
            if (method_exists($instance, 'boot')) {
                $instance->boot();
            }
        }
    }

    protected function getProviders(): array
    {
        return $this->metadata['providers'] ?? [];
    }

    protected function registerAutoload(): void
    {
        $autoload = $this->metadata['autoload']['psr-4'] ?? [];

        foreach ($autoload as $ns => $dir) {
            $libPath = $this->path . '/' . ltrim($dir, '/');

            spl_autoload_register(function (string $class) use ($ns, $libPath): void {
                if (str_starts_with($class, $ns)) {
                    $relative = substr($class, strlen($ns));
                    $file = $libPath . str_replace('\\', '/', $relative) . '.php';
                    if (file_exists($file)) {
                        require $file;
                    }
                }
            });
        }
    }
}
