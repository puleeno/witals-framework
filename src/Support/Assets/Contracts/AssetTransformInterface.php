<?php

declare(strict_types=1);

namespace Witals\Framework\Support\Assets\Contracts;

interface AssetTransformInterface
{
    public function transformContent(string $content, string $fromPath, string $toPath): string;
    public function transformUrl(string $url, string $fromPath, string $toPath): string;
    public function addRule(string $pattern, string $replacement): void;
    public function getRules(): array;
}
