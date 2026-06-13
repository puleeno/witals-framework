<?php

declare(strict_types=1);

namespace Witals\Framework\Context;

use Witals\Framework\Context\Contracts\BlockInterface;
use Witals\Framework\Context\Contracts\BlockManagerInterface;
use Witals\Framework\Context\Contracts\ContextInterface;

class BlockManager implements BlockManagerInterface
{
    protected array $blocks = [];
    protected array $categoryIndex = [];
    protected array $scopeIndex = [];

    public function registerBlock(BlockInterface $block): void
    {
        $id = $block->getId();

        $this->blocks[$id] = $block;
        $this->categoryIndex[$block->getCategory()][$id] = $block;
        $this->scopeIndex[$block->getScope()][$id] = $block;
    }

    public function unregisterBlock(string $id): void
    {
        if (!isset($this->blocks[$id])) {
            return;
        }

        $block = $this->blocks[$id];
        unset($this->categoryIndex[$block->getCategory()][$id]);
        unset($this->scopeIndex[$block->getScope()][$id]);
        unset($this->blocks[$id]);
    }

    public function getBlock(string $id): ?BlockInterface
    {
        return $this->blocks[$id] ?? null;
    }

    public function hasBlock(string $id): bool
    {
        return isset($this->blocks[$id]);
    }

    public function getBlocksForContext(?ContextInterface $context = null): array
    {
        if ($context === null) {
            return $this->getGlobalBlocks();
        }

        $available = $this->getGlobalBlocks();

        foreach ($this->blocks as $block) {
            if ($block->isAvailableIn($context)) {
                $available[$block->getId()] = $block;
            }
        }

        return $available;
    }

    public function getGlobalBlocks(): array
    {
        return array_values($this->scopeIndex[BlockInterface::SCOPE_GLOBAL] ?? []);
    }

    public function getBlocksByCategory(string $category): array
    {
        return array_values($this->categoryIndex[$category] ?? []);
    }

    public function getBlocksByScope(string $scope): array
    {
        return array_values($this->scopeIndex[$scope] ?? []);
    }

    public function getAllBlocks(): array
    {
        return array_values($this->blocks);
    }

    public function getCategories(): array
    {
        return array_keys($this->categoryIndex);
    }
}
