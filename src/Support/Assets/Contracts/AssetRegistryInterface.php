<?php

declare(strict_types=1);

namespace Witals\Framework\Support\Assets\Contracts;

interface AssetRegistryInterface
{
    public const MODE_SSR = 'ssr';
    public const MODE_CSR = 'csr';

    public function register(string $handle, string $src, array $deps = [], string $version = '', array $attributes = []): void;
    public function enqueue(string $handle): void;
    public function dequeue(string $handle): void;
    public function isRegistered(string $handle): bool;
    public function isEnqueued(string $handle): bool;
    public function get(string $handle): ?array;
    public function getRegistered(): array;
    public function getEnqueued(): array;
    public function resolveDeps(string $handle): array;

    public function setRenderMode(string $mode): void;
    public function getRenderMode(): string;

    public function renderCss(): string;
    public function renderJs(): string;
    public function getManifest(): array;

    public function setContext(string $context): void;
    public function getContext(): string;
}
