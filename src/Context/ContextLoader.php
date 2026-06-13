<?php

declare(strict_types=1);

namespace Witals\Framework\Context;

use Witals\Framework\Context\Contracts\BlockInterface;
use Witals\Framework\Context\Contracts\BlockManagerInterface;
use Witals\Framework\Context\Contracts\ContextInterface;
use Witals\Framework\Context\Contracts\ContextLoaderInterface;
use Witals\Framework\Context\Contracts\ContextManagerInterface;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class ContextLoader implements ContextLoaderInterface
{
    protected array $collectedStyles = [];
    protected array $collectedScripts = [];
    protected array $renderedBlocks = [];
    protected array $renderedCsrBlocks = [];

    protected string $criticalCss = '';
    protected string $deferredCss = '';

    public function __construct(
        protected BlockManagerInterface $blockManager,
        protected string $templateDir = '',
        protected ?ContextManagerInterface $contextManager = null,
        protected string $apiBase = '/api/context/block',
    ) {}

    public function load(ContextInterface $context): Response
    {
        $this->reset();

        $html = $this->renderDocument($context);
        $this->reset();

        return Response::html($html);
    }

    public function renderBlocks(array $blockTree, ContextInterface $context): string
    {
        $output = '';

        foreach ($blockTree as $node) {
            $output .= $this->renderNode($node, $context);
        }

        return $output;
    }

    public function renderBlock(BlockInterface $block, array $attributes, array $children, ContextInterface $context, string $mode = BlockInterface::MODE_SSR, bool $insideCsr = false): string
    {
        $blockId = $block->getId();

        $this->collectBlockAssets($block, $blockId);

        $supports = $block->getSupports();
        $lazy = in_array('lazy', $supports, true) && $this->isBelowFold(count($this->renderedBlocks));

        if ($lazy && $mode === BlockInterface::MODE_SSR) {
            $mode = BlockInterface::MODE_HYBRID;
        }

        $output = $this->openWrapper($blockId, $attributes, $mode, $block);

        switch ($mode) {
            case BlockInterface::MODE_CSR:
                if ($insideCsr) {
                    $output .= $block->renderSkeleton($attributes, $context);
                } else {
                    $output .= $this->renderCsrBlock($block, $attributes, $context);
                }
                break;

            case BlockInterface::MODE_HYBRID:
                $fullContent = $block->render($attributes, $children, $context);
                if ($insideCsr) {
                    $output .= $fullContent;
                } else {
                    $output .= $this->renderHybridBlock($block, $attributes, $fullContent, $context);
                }
                break;

            case BlockInterface::MODE_SSR:
            default:
                $output .= $block->render($attributes, $children, $context);
                break;
        }

        $output .= '</div>';

        $this->renderedBlocks[] = $blockId;

        return $output;
    }

    public function collectStyles(ContextInterface $context): array
    {
        return $this->collectedStyles;
    }

    public function collectScripts(ContextInterface $context): array
    {
        return $this->collectedScripts;
    }

    public function renderCriticalCss(ContextInterface $context): string
    {
        if ($this->criticalCss === '') {
            $this->buildCriticalCss();
        }

        if ($this->criticalCss === '') {
            return '';
        }

        return '<style id="witals-critical-css">' . $this->criticalCss . '</style>';
    }

    public function renderDeferredCss(ContextInterface $context): string
    {
        if ($this->deferredCss === '') {
            $this->buildDeferredCss();
        }

        if ($this->deferredCss === '') {
            return '';
        }

        $links = '';
        foreach (explode("\n", trim($this->deferredCss)) as $href) {
            $href = trim($href);
            if ($href === '') {
                continue;
            }
            $links .= '<link rel="preload" href="' . $href . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
            $links .= "\n";
            $links .= '<noscript><link rel="stylesheet" href="' . $href . '"></noscript>';
            $links .= "\n";
        }

        return $links;
    }

    public function renderScripts(ContextInterface $context): string
    {
        if (empty($this->collectedScripts)) {
            return '';
        }

        $output = '';
        $seen = [];

        foreach ($this->collectedScripts as $entry) {
            $src = $entry['src'] ?? '';
            if ($src === '' || isset($seen[$src])) {
                continue;
            }
            $seen[$src] = true;
            $attrs = $entry['attributes'] ?? [];
            $attrStr = '';

            foreach ($attrs as $key => $value) {
                if ($value === true) {
                    $attrStr .= ' ' . $key;
                } else {
                    $attrStr .= ' ' . $key . '="' . $value . '"';
                }
            }

            $output .= '<script src="' . $src . '"' . $attrStr . '></script>';
            $output .= "\n";
        }

        if (!empty($this->renderedCsrBlocks)) {
            $output .= $this->renderCsrBootScript();
        }

        return $output;
    }

    protected function renderDocument(ContextInterface $context): string
    {
        $blockTree = $context->getBlockTree();
        $content = $this->renderBlocks($blockTree, $context);

        $metadata = $context->getMetadata();
        $title = $metadata['title'] ?? $context->getLabel();
        $description = $metadata['description'] ?? '';

        $criticalCss = $this->renderCriticalCss($context);
        $deferredCss = $this->renderDeferredCss($context);
        $scripts = $this->renderScripts($context);

        $template = $context->getTemplate();
        if ($template && $this->templateDir) {
            $templateFile = $this->templateDir . '/' . $template . '.php';
            if (file_exists($templateFile)) {
                ob_start();
                extract([
                    'context' => $context,
                    'content' => $content,
                    'title' => $title,
                    'description' => $description,
                    'criticalCss' => $criticalCss,
                    'deferredCss' => $deferredCss,
                    'scripts' => $scripts,
                    'metadata' => $metadata,
                ]);
                require $templateFile;
                return ob_get_clean();
            }
        }

        return $this->renderDefaultTemplate($title, $description, $criticalCss, $deferredCss, $content, $scripts);
    }

    protected function renderDefaultTemplate(string $title, string $description, string $criticalCss, string $deferredCss, string $content, string $scripts): string
    {
        return '<!DOCTYPE html>' . "\n"
            . '<html lang="vi">' . "\n"
            . '<head>' . "\n"
            . '<meta charset="UTF-8">' . "\n"
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n"
            . '<title>' . $title . '</title>' . "\n"
            . ($description ? '<meta name="description" content="' . $description . '">' . "\n" : '')
            . $criticalCss . "\n"
            . $deferredCss . "\n"
            . '</head>' . "\n"
            . '<body>' . "\n"
            . $content . "\n"
            . $scripts . "\n"
            . '</body>' . "\n"
            . '</html>';
    }

    protected function renderNode(array $node, ContextInterface $context, ?string $parentMode = null, bool $insideCsr = false): string
    {
        $blockId = $node['blockId'] ?? '';
        $attributes = $node['attributes'] ?? [];
        $children = $node['children'] ?? [];
        $mode = $node['mode'] ?? BlockInterface::MODE_SSR;

        if ($parentMode === BlockInterface::MODE_CSR || $parentMode === BlockInterface::MODE_HYBRID) {
            $mode = BlockInterface::MODE_CSR;
            $insideCsr = true;
        }

        if ($blockId === '') {
            return '';
        }

        if ($blockId === '@core/html') {
            return $attributes['content'] ?? '';
        }

        $block = $this->blockManager->getBlock($blockId);

        if ($block === null) {
            return '';
        }

        foreach ($children as $child) {
            $this->renderNode($child, $context, $mode, $insideCsr);
        }

        return $this->renderBlock($block, $attributes, $children, $context, $mode, $insideCsr);
    }

    public function renderBlockContent(string $blockId, array $attributes, string $contextType, string $identifier): Response
    {
        $block = $this->blockManager->getBlock($blockId);

        if ($block === null) {
            return Response::json(['error' => 'Block not found'], 404);
        }

        $context = $this->contextManager?->getContext($contextType, $identifier);

        if ($context === null) {
            return Response::json(['error' => 'Context not found'], 404);
        }

        $html = $block->render($attributes, [], $context);

        return Response::json([
            'blockId' => $blockId,
            'html' => $html,
            'contextType' => $contextType,
            'identifier' => $identifier,
        ]);
    }

    protected function renderCsrBlock(BlockInterface $block, array $attributes, ContextInterface $context): string
    {
        $skeleton = $block->renderSkeleton($attributes, $context);

        $dataAttrs = ' data-witals-block="' . $block->getId() . '"'
            . ' data-witals-mode="csr"'
            . ' data-witals-context="' . $context->getType() . '"'
            . ' data-witals-id="' . $context->getIdentifier() . '"';

        if (!empty($attributes)) {
            $dataAttrs .= ' data-witals-attrs="' . base64_encode(json_encode($attributes)) . '"';
        }

        $this->renderedCsrBlocks[] = [
            'blockId' => $block->getId(),
            'contextType' => $context->getType(),
            'identifier' => $context->getIdentifier(),
            'attributes' => $attributes,
        ];

        return '<div class="witals-csr-content"' . $dataAttrs . '>'
            . $skeleton
            . '</div>';
    }

    protected function renderHybridBlock(BlockInterface $block, array $attributes, string $content, ContextInterface $context): string
    {
        $dataAttrs = ' data-witals-block="' . $block->getId() . '"'
            . ' data-witals-mode="hybrid"'
            . ' data-witals-context="' . $context->getType() . '"'
            . ' data-witals-id="' . $context->getIdentifier() . '"';

        if (!empty($attributes)) {
            $dataAttrs .= ' data-witals-attrs="' . base64_encode(json_encode($attributes)) . '"';
        }

        $this->renderedCsrBlocks[] = [
            'blockId' => $block->getId(),
            'contextType' => $context->getType(),
            'identifier' => $context->getIdentifier(),
            'attributes' => $attributes,
        ];

        return '<div class="witals-hybrid-content"' . $dataAttrs . '>'
            . $content
            . '</div>';
    }

    protected function renderCsrBootScript(): string
    {
        if (empty($this->renderedCsrBlocks)) {
            return '';
        }

        $blocksJson = json_encode($this->renderedCsrBlocks);

        return '<script>'
            . '(function(){'
            . 'var blocks=' . $blocksJson . ';'
            . 'var observer=new IntersectionObserver(function(entries){'
            . 'entries.forEach(function(entry){'
            . 'if(entry.isIntersecting){'
            . 'var el=entry.target;'
            . 'if(el.dataset.loaded)return;'
            . 'el.dataset.loaded="1";'
            . 'var blockId=el.dataset.block;'
            . 'var endpoint=el.dataset.endpoint;'
            . 'if(endpoint){'
            . 'fetch(endpoint)'
            . '.then(function(r){return r.text()})'
            . '.then(function(html){el.innerHTML=html})'
            . '.catch(function(){el.innerHTML="<p>Không thể tải nội dung</p>"});'
            . '}'
            . '}'
            . '});'
            . '},{rootMargin:"200px"});'
            . 'document.querySelectorAll("[data-csr],[data-hybrid]").forEach(function(el){observer.observe(el)});'
            . '})();'
            . '</script>';
    }

    protected function openWrapper(string $blockId, array $attributes, string $mode, BlockInterface $block): string
    {
        $anchor = $attributes['anchor'] ?? '';
        $cssClass = $attributes['className'] ?? '';
        $defaultClass = 'wp-block-' . str_replace('/', '-', $blockId);

        $classes = [$defaultClass];
        if ($mode === BlockInterface::MODE_CSR) {
            $classes[] = 'witals-block-csr';
        } elseif ($mode === BlockInterface::MODE_HYBRID) {
            $classes[] = 'witals-block-hybrid';
        }
        if ($cssClass) {
            $classes[] = $cssClass;
        }

        $wrapper = '<div';
        if ($anchor) {
            $wrapper .= ' id="' . $anchor . '"';
        }
        $wrapper .= ' class="' . implode(' ', $classes) . '"';
        $wrapper .= '>';

        return $wrapper;
    }

    protected function reset(): void
    {
        $this->collectedStyles = [];
        $this->collectedScripts = [];
        $this->renderedBlocks = [];
        $this->renderedCsrBlocks = [];
        $this->criticalCss = '';
        $this->deferredCss = '';
    }

    protected function collectBlockAssets(BlockInterface $block, string $blockId): void
    {
        $styles = $block->getStyles();
        if (!empty($styles)) {
            foreach ($styles as $style) {
                $this->collectedStyles[$blockId][] = $style;
            }
        }

        $scripts = $block->getScripts();
        if (!empty($scripts)) {
            foreach ($scripts as $script) {
                $this->collectedScripts[] = $script;
            }
        }
    }

    protected function isBelowFold(int $blockIndex): bool
    {
        return $blockIndex >= $this->foldBlockCount();
    }

    protected function foldBlockCount(): int
    {
        return 3;
    }

    protected function buildCriticalCss(): void
    {
        $cssParts = [];
        foreach ($this->collectedStyles as $blockId => $styles) {
            if ($this->isAboveFold($blockId)) {
                continue;
            }
            foreach ($styles as $style) {
                if (isset($style['critical']) && $style['critical']) {
                    $cssParts[] = $style['content'] ?? '';
                }
            }
        }
        $this->criticalCss = implode("\n", $cssParts);
    }

    protected function buildDeferredCss(): void
    {
        $links = [];
        foreach ($this->collectedStyles as $blockId => $styles) {
            foreach ($styles as $style) {
                if (isset($style['href'])) {
                    $links[] = $style['href'];
                }
            }
        }
        $this->deferredCss = implode("\n", array_unique($links));
    }

    protected function isAboveFold($blockIdOrIndex): bool
    {
        if (is_string($blockIdOrIndex)) {
            $index = array_search($blockIdOrIndex, $this->renderedBlocks, true);
            if ($index === false) {
                return false;
            }
            return $index < $this->foldBlockCount();
        }
        return false;
    }
}
