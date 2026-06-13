<?php

declare(strict_types=1);

namespace Witals\Framework\Context\Contracts;

use Witals\Framework\Http\Request;

interface ContextInterface
{
    public function getType(): string;

    public function getIdentifier(): string;

    public function getLabel(): string;

    public function getDescription(): string;

    public function getData(): array;

    public function setData(array $data): void;

    public function getBlockTree(): array;

    public function setBlockTree(array $blockTree): void;

    public function getMetadata(): array;

    public function getTemplate(): ?string;

    public function getHierarchy(): array;

    public function getThemeTemplateDir(): ?string;

    public function matches(Request $request): bool;
}
