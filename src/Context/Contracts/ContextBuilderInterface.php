<?php

declare(strict_types=1);

namespace Witals\Framework\Context\Contracts;

interface ContextBuilderInterface
{
    public function getLayout(string $type, string $identifier): array;

    public function saveLayout(string $type, string $identifier, array $blockTree): void;

    public function resetLayout(string $type, string $identifier): void;

    public function getDefaultLayout(string $type, string $identifier): array;

    public function getAvailableBlocks(?ContextInterface $context = null): array;

    public function getEditorData(string $type, string $identifier): array;

    public function renderPreview(string $type, string $identifier, array $blockTree = []): string;
}
