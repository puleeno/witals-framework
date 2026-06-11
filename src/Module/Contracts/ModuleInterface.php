<?php

declare(strict_types=1);

namespace Witals\Framework\Module\Contracts;

use Witals\Framework\Module\ModuleFunction;

interface ModuleInterface
{
    public function getName(): string;

    public function getVersion(): string;

    public function getDescription(): string;

    public function getType(): string;

    public function getPriority(): int;

    public function getDependencies(): array;

    public function getProvides(): array;

    public function getConsumes(): array;

    public function isEnabled(): bool;

    public function register(): void;

    public function boot(): void;

    public function getPath(): string;

    public function getRoutePrefix(): string;

    public function getRoutes(): array;

    public function getFunctions(): array;

    public function hasFunction(string $name): bool;

    public function getFunction(string $name): ?ModuleFunction;
}
