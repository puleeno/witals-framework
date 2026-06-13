<?php

declare(strict_types=1);

namespace Witals\Framework\Context\Contracts;

interface BlockManagerInterface
{
    public function registerBlock(BlockInterface $block): void;

    public function unregisterBlock(string $id): void;

    public function getBlock(string $id): ?BlockInterface;

    public function hasBlock(string $id): bool;

    public function getBlocksForContext(?ContextInterface $context = null): array;

    public function getGlobalBlocks(): array;

    public function getBlocksByCategory(string $category): array;

    public function getBlocksByScope(string $scope): array;

    public function getAllBlocks(): array;

    public function getCategories(): array;
}
