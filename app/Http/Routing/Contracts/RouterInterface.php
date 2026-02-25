<?php

declare(strict_types=1);

namespace App\Http\Routing\Contracts;

use Witals\Framework\Http\Request;

interface RouterInterface
{
    public function get(string $path, mixed $action): mixed;
    public function post(string $path, mixed $action): mixed;
    public function put(string $path, mixed $action): mixed;
    public function delete(string $path, mixed $action): mixed;
    public function dispatch(Request $request): mixed;
    public function setWordPressFallback(callable $fallback): void;
    public function getRoutes(): array;
}
