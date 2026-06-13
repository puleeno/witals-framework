<?php

declare(strict_types=1);

namespace Witals\Framework\Context\Contracts;

use Witals\Framework\Http\Response;

interface ContextLoaderInterface
{
    public const FORMAT_HTML = 'html';
    public const FORMAT_JSON = 'json';
    public const FORMAT_XML  = 'xml';

    public function load(ContextInterface $context, string $format = self::FORMAT_HTML): Response;

    public function renderBlocks(array $blockTree, ContextInterface $context): string;

    public function renderBlock(BlockInterface $block, array $attributes, array $children, ContextInterface $context, string $mode = BlockInterface::MODE_SSR, bool $insideCsr = false): string;

    public function renderBlockContent(string $blockId, array $attributes, string $contextType, string $identifier): Response;

    public function collectStyles(ContextInterface $context): array;

    public function collectScripts(ContextInterface $context): array;

    public function renderCriticalCss(ContextInterface $context): string;

    public function renderDeferredCss(ContextInterface $context): string;

    public function renderScripts(ContextInterface $context): string;

    public function toArray(ContextInterface $context): array;

    public function toJson(ContextInterface $context): string;

    public function toXml(ContextInterface $context): string;
}
