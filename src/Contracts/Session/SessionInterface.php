<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts\Session;

interface SessionInterface
{
    public function start(): void;
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function has(string $key): bool;
    public function remove(string $key): void;
    public function clear(): void;
    public function destroy(): void;
    public function getId(): string;
    public function regenerate(): void;
}
