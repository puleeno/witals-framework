<?php

declare(strict_types=1);

namespace Witals\Framework\Support\Assets;

use Witals\Framework\Support\Assets\Contracts\AssetRegistryInterface;
use Witals\Framework\Support\Assets\Contracts\AssetTransformInterface;

class AssetRegistry implements AssetRegistryInterface
{
    protected array $registry = [];
    protected array $enqueued = [];
    protected string $renderMode = self::MODE_SSR;
    protected string $context = 'frontend';
    protected string $baseUrl = '';
    protected string $publicPath = '';
    protected array $roots = [];
    protected ?array $manifest = null;
    protected ?AssetTransformInterface $transformer = null;

    protected array $contextDefaults = [
        'frontend' => ['mode' => self::MODE_SSR],
        'admin'    => ['mode' => self::MODE_CSR],
    ];

    public function __construct(
        string $baseUrl = '',
        string $publicPath = '',
        ?AssetTransformInterface $transformer = null,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->publicPath = $publicPath;
        $this->transformer = $transformer;
    }

    public function loadConfig(array $config): void
    {
        if (isset($config['contexts'])) {
            foreach ($config['contexts'] as $ctx => $settings) {
                $this->contextDefaults[$ctx] = ['mode' => $settings['mode'] ?? self::MODE_SSR];
                if (isset($settings['roots'])) {
                    foreach ($settings['roots'] as $root) {
                        $this->addRoot($root['path'], $root['url']);
                    }
                }
            }
        }

        if (isset($config['register'])) {
            foreach ($config['register'] as $handle => $asset) {
                $this->register(
                    handle: $handle,
                    src: $asset['src'],
                    deps: $asset['deps'] ?? [],
                    version: $asset['version'] ?? '',
                    attributes: $asset['attributes'] ?? [],
                );
            }
        }

        if (isset($config['transforms']) && $this->transformer !== null) {
            foreach ($config['transforms'] as $rule) {
                foreach ($rule['patterns'] ?? [] as $pattern => $replacement) {
                    $this->transformer->addRule($pattern, $replacement);
                }
            }
        }
    }

    public function register(string $handle, string $src, array $deps = [], string $version = '', array $attributes = []): void
    {
        $this->registry[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'version' => $version,
            'attributes' => $attributes,
            'type' => $this->detectType($src),
        ];
    }

    public function enqueue(string $handle): void
    {
        if (!isset($this->registry[$handle])) {
            return;
        }
        $this->enqueued[$handle] = true;
    }

    public function dequeue(string $handle): void
    {
        unset($this->enqueued[$handle]);
    }

    public function isRegistered(string $handle): bool
    {
        return isset($this->registry[$handle]);
    }

    public function isEnqueued(string $handle): bool
    {
        return isset($this->enqueued[$handle]);
    }

    public function get(string $handle): ?array
    {
        return $this->registry[$handle] ?? null;
    }

    public function getRegistered(): array
    {
        return $this->registry;
    }

    public function getEnqueued(): array
    {
        $handles = array_keys($this->enqueued);
        $assets = [];
        foreach ($handles as $handle) {
            if (isset($this->registry[$handle])) {
                $assets[$handle] = $this->registry[$handle];
            }
        }
        return $assets;
    }

    public function resolveDeps(string $handle): array
    {
        $resolved = [];
        $this->resolveDepsRecursive($handle, $resolved);
        return $resolved;
    }

    protected function resolveDepsRecursive(string $handle, array &$resolved, array &$visited = []): void
    {
        if (in_array($handle, $visited, true)) {
            return;
        }
        $visited[] = $handle;

        if (!isset($this->registry[$handle])) {
            return;
        }

        foreach ($this->registry[$handle]['deps'] as $dep) {
            $this->resolveDepsRecursive($dep, $resolved, $visited);
        }

        if (!in_array($handle, $resolved, true)) {
            $resolved[] = $handle;
        }
    }

    public function setRenderMode(string $mode): void
    {
        if (!in_array($mode, [self::MODE_SSR, self::MODE_CSR], true)) {
            return;
        }
        $this->renderMode = $mode;
    }

    public function getRenderMode(): string
    {
        return $this->renderMode;
    }

    public function setContext(string $context): void
    {
        $this->context = $context;
        if (isset($this->contextDefaults[$context])) {
            $this->renderMode = $this->contextDefaults[$context]['mode'];
        }
        $this->enqueued = [];
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function renderCss(): string
    {
        if ($this->renderMode === self::MODE_CSR) {
            return $this->renderCsrCss();
        }
        return $this->renderSsrCss();
    }

    public function renderJs(): string
    {
        if ($this->renderMode === self::MODE_CSR) {
            return $this->renderCsrJs();
        }
        return $this->renderSsrJs();
    }

    public function getManifest(): array
    {
        $enqueued = $this->getEnqueued();
        $manifest = [];

        foreach (array_keys($enqueued) as $handle) {
            $deps = $this->resolveDeps($handle);
            $manifest[$handle] = [
                'src' => $this->resolveUrl($this->registry[$handle]['src']),
                'deps' => $deps,
                'version' => $this->registry[$handle]['version'],
            ];
        }

        return $manifest;
    }

    public function addRoot(string $path, string $url): self
    {
        $this->roots[] = [
            'path' => rtrim($path, '/'),
            'url' => rtrim($url, '/'),
        ];
        return $this;
    }

    public function loadManifest(string $path): void
    {
        if (file_exists($path)) {
            $content = file_get_contents($path);
            $this->manifest = json_decode($content, true) ?: [];
        }
    }

    protected function renderSsrCss(): string
    {
        $sorted = $this->getSortedEnqueued('css');
        $html = '';

        foreach ($sorted as $handle => $asset) {
            $url = $this->resolveUrl($asset['src']);
            $media = $asset['attributes']['media'] ?? 'all';
            $html .= "<link rel=\"stylesheet\" id=\"{$handle}-css\" href=\"{$url}\" type=\"text/css\" media=\"{$media}\">\n";
        }

        return $html;
    }

    protected function renderSsrJs(): string
    {
        $sorted = $this->getSortedEnqueued('js');
        $html = '';

        foreach ($sorted as $handle => $asset) {
            $url = $this->resolveUrl($asset['src']);
            $async = !empty($asset['attributes']['async']) ? ' async' : '';
            $defer = !empty($asset['attributes']['defer']) ? ' defer' : '';
            $type = isset($asset['attributes']['type']) ? " type=\"{$asset['attributes']['type']}\"" : '';
            $html .= "<script src=\"{$url}\" id=\"{$handle}-js\"{$async}{$defer}{$type}></script>\n";
        }

        return $html;
    }

    protected function renderCsrCss(): string
    {
        $sorted = $this->getSortedEnqueued('css');

        if (empty($sorted)) {
            return '';
        }

        $html = '';
        $inlineStyles = '';

        foreach ($sorted as $asset) {
            $path = $this->resolvePath($asset['src']);
            if ($path !== null && file_exists($path)) {
                $inlineStyles .= file_get_contents($path) . "\n";
            }
        }

        if ($inlineStyles !== '') {
            $html = "<style id=\"admin-critical-css\">\n{$inlineStyles}\n</style>\n";
        }

        return $html;
    }

    protected function renderCsrJs(): string
    {
        $sorted = $this->getSortedEnqueued('js');

        if (empty($sorted)) {
            return '';
        }

        $html = '';

        $manifest = $this->getManifest();
        if (!empty($manifest)) {
            $json = json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $html .= "<script>window.__ASSET_MANIFEST__ = {$json};</script>\n";
        }

        foreach ($sorted as $handle => $asset) {
            $url = $this->resolveUrl($asset['src']);
            $type = $asset['attributes']['type'] ?? 'module';
            $defer = !empty($asset['attributes']['defer']) || true ? ' defer' : '';
            $html .= "<script src=\"{$url}\" id=\"{$handle}-js\" type=\"{$type}\"{$defer}></script>\n";
        }

        return $html;
    }

    protected function getSortedEnqueued(string $type): array
    {
        $enqueued = $this->getEnqueued();
        $filtered = [];

        foreach ($enqueued as $handle => $asset) {
            if ($this->detectType($asset['src']) === $type) {
                $sortedDeps = $this->resolveDeps($handle);
                $filtered[$handle] = $asset;
            }
        }

        return $filtered;
    }

    protected function detectType(string $src): string
    {
        return preg_match('/\.css(\?|$)/i', $src) ? 'css' : 'js';
    }

    protected function resolveUrl(string $path): string
    {
        if (preg_match('/^https?:\/\//', $path)) {
            return $path;
        }

        $path = ltrim($path, '/');

        if ($this->manifest !== null && isset($this->manifest[$path])) {
            $path = ltrim($this->manifest[$path], '/');
        }

        if ($this->transformer !== null) {
            $path = $this->transformer->transformUrl($path, '', '');
        }

        foreach (array_reverse($this->roots) as $root) {
            $fullPath = $root['path'] . '/' . $path;
            if (file_exists($fullPath)) {
                return $root['url'] . '/' . $path;
            }
        }

        if ($this->transformer !== null) {
            return $this->transformer->transformUrl($path, '', $this->baseUrl . '/');
        }

        return $this->baseUrl . '/' . $path;
    }

    protected function resolvePath(string $src): ?string
    {
        $path = ltrim($src, '/');

        foreach (array_reverse($this->roots) as $root) {
            $fullPath = $root['path'] . '/' . $path;
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return $this->publicPath ? ($this->publicPath . '/' . $path) : null;
    }
}
