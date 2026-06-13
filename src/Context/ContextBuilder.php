<?php

declare(strict_types=1);

namespace Witals\Framework\Context;

use Witals\Framework\Context\Contracts\BlockManagerInterface;
use Witals\Framework\Context\Contracts\ContextBuilderInterface;
use Witals\Framework\Context\Contracts\ContextInterface;
use Witals\Framework\Context\Contracts\ContextLoaderInterface;
use Witals\Framework\Context\Contracts\ContextManagerInterface;

class ContextBuilder implements ContextBuilderInterface
{
    public function __construct(
        protected ContextManagerInterface $contextManager,
        protected BlockManagerInterface $blockManager,
        protected ContextLoaderInterface $loader,
        protected LayoutStorage $storage,
    ) {}

    public function getLayout(string $type, string $identifier): array
    {
        $saved = $this->storage->get($type, $identifier);

        if ($saved !== null) {
            return $saved;
        }

        return $this->getDefaultLayout($type, $identifier);
    }

    public function saveLayout(string $type, string $identifier, array $blockTree): void
    {
        $this->storage->set($type, $identifier, $blockTree);
    }

    public function resetLayout(string $type, string $identifier): void
    {
        $this->storage->delete($type, $identifier);
    }

    public function getDefaultLayout(string $type, string $identifier): array
    {
        $context = $this->contextManager->getContext($type, $identifier);

        if ($context === null) {
            return [];
        }

        return $context->getBlockTree();
    }

    public function getAvailableBlocks(?ContextInterface $context = null): array
    {
        return $this->blockManager->getBlocksForContext($context);
    }

    public function getEditorData(string $type, string $identifier): array
    {
        $context = $this->contextManager->getContext($type, $identifier);

        return [
            'context' => $context ? [
                'type' => $context->getType(),
                'identifier' => $context->getIdentifier(),
                'label' => $context->getLabel(),
                'data' => $context->getData(),
            ] : null,
            'layout' => $this->getLayout($type, $identifier),
            'availableBlocks' => array_map(function ($block) {
                return [
                    'id' => $block->getId(),
                    'name' => $block->getName(),
                    'description' => $block->getDescription(),
                    'category' => $block->getCategory(),
                    'icon' => $block->getIcon(),
                    'scope' => $block->getScope(),
                    'supports' => $block->getSupports(),
                    'defaultAttributes' => $block->getDefaultAttributes(),
                    'editorComponent' => $block->getEditorComponent(),
                ];
            }, $this->getAvailableBlocks($context)),
        ];
    }

    public function renderPreview(string $type, string $identifier, array $blockTree = []): string
    {
        $context = $this->contextManager->getContext($type, $identifier);

        if ($context === null) {
            return '';
        }

        if (!empty($blockTree)) {
            $context->setBlockTree($blockTree);
        }

        return $this->loader->renderBlocks($context->getBlockTree(), $context);
    }
}
