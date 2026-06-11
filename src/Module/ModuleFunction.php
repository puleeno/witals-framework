<?php

declare(strict_types=1);

namespace Witals\Framework\Module;

class ModuleFunction
{
    protected string $name;
    protected string $fullName;
    protected array $metadata;
    protected ?ModuleFunction $parent = null;
    protected array $children = [];

    public function __construct(
        protected string $moduleName,
        string $name,
        array $metadata = [],
        ?ModuleFunction $parent = null,
    ) {
        $this->name = $name;
        $this->metadata = $metadata;
        $this->parent = $parent;
        $this->fullName = $parent !== null
            ? $parent->getFullName() . '.' . $name
            : $moduleName . '.' . $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFullName(): string
    {
        return $this->fullName;
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

    public function getProviders(): array
    {
        return $this->metadata['providers'] ?? [];
    }

    public function getBootstrap(): ?string
    {
        return $this->metadata['bootstrap'] ?? null;
    }

    public function isEnabled(): bool
    {
        if ($this->parent !== null && !$this->parent->isEnabled()) {
            return false;
        }
        return $this->metadata['enabled'] ?? true;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->metadata['enabled'] = $enabled;
    }

    public function getParent(): ?ModuleFunction
    {
        return $this->parent;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function hasChild(string $name): bool
    {
        return isset($this->children[$name]);
    }

    public function getChild(string $name): ?ModuleFunction
    {
        return $this->children[$name] ?? null;
    }

    public function addChild(ModuleFunction $child): void
    {
        $this->children[$child->getName()] = $child;
    }

    public function getRoutePrefix(): string
    {
        $prefix = $this->metadata['route_prefix'] ?? '';

        if ($prefix === '' && $this->parent !== null) {
            return $this->parent->getRoutePrefix();
        }

        return $prefix;
    }

    public function getRoutes(): array
    {
        return $this->metadata['routes'] ?? [];
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function flatten(): array
    {
        $result = [
            $this->fullName => [
                'name' => $this->fullName,
                'type' => $this->getType(),
                'description' => $this->getDescription(),
                'priority' => $this->getPriority(),
                'enabled' => $this->isEnabled(),
                'depends' => $this->getDependencies(),
                'provides' => $this->getProvides(),
                'consumes' => $this->getConsumes(),
                'providers' => $this->getProviders(),
                'bootstrap' => $this->getBootstrap(),
                'route_prefix' => $this->getRoutePrefix(),
                'routes' => $this->getRoutes(),
                '_function' => true,
                '_full_name' => $this->fullName,
                '_module' => $this->moduleName,
                '_parent' => $this->parent?->getFullName(),
            ],
        ];

        foreach ($this->children as $child) {
            $result += $child->flatten();
        }

        return $result;
    }
}
