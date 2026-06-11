<?php

declare(strict_types=1);

namespace Witals\Framework\Module\Contracts;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

interface ModuleManagerInterface
{
    public function discover(): void;

    public function buildRouteIndex(): array;

    public function matchRoute(string $method, string $path): ?string;

    public function load(string $name): ?ModuleInterface;

    public function dispatch(Request $request): ?Response;

    public function all(): array;

    public function isLoaded(string $name): bool;
}
