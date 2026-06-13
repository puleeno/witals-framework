<?php

declare(strict_types=1);

namespace Witals\Framework\Context\Contracts;

interface BlockInterface
{
    public const SCOPE_GLOBAL = 'global';
    public const SCOPE_CONTEXT = 'context';
    public const SCOPE_CONTEXT_TYPE = 'context_type';

    public const MODE_SSR = 'ssr';
    public const MODE_CSR = 'csr';
    public const MODE_HYBRID = 'hybrid';

    public function getId(): string;

    public function getName(): string;

    public function getDescription(): string;

    public function getScope(): string;

    public function getScopedTo(): ?string;

    public function getCategory(): string;

    public function getIcon(): string;

    public function getSupports(): array;

    public function getKeywords(): array;

    public function getDefaultAttributes(): array;

    public function getDefaultRenderMode(): string;

    public function render(array $attributes, array $children, ContextInterface $context): string;

    public function renderSkeleton(array $attributes, ContextInterface $context): string;

    public function getCsrEndpoint(): ?string;

    public function getEditorComponent(): ?string;

    public function getStyles(): array;

    public function getScripts(): array;

    public function isAvailableIn(ContextInterface $context): bool;
}
