<?php

declare(strict_types=1);

namespace Witals\Framework\Support;

use Witals\Framework\Application;

/**
 * Intelligent Asset Manager
 * Handles CSS/JS enqueuing, dependency resolution, discovery, and context-aware rendering.
 * Supports WordPress-like structure with roots and handles.
 */
class AssetManager
{
    protected Application $app;
    protected string $baseUrl;
    protected ?array $manifest = null;
    protected string $publicPath;

    protected array $css = [];
    protected array $js = [];
    protected string $mode = 'external'; // 'external' (tags) or 'internal' (inline)
    
    // Default configurations per context
    protected array $contextDefaults = [
        'frontend' => ['mode' => 'internal'],
        'admin'    => ['mode' => 'external'],
    ];

    // Registry for pre-defined assets (handles)
    protected array $registry = [
        'css' => [],
        'js' => [],
    ];

    // Discovery roots (search paths)
    protected array $roots = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
        // Use relative URLs by default (works with any domain)
        $this->baseUrl = rtrim(env('APP_URL', ''), '/');
        $this->publicPath = $app->basePath('public');
        
        // Add default public root
        $this->addRoot($this->publicPath, $this->baseUrl);
    }

    /**
     * Add a discovery root for assets (e.g. for themes or plugins)
     */
    public function addRoot(string $path, string $url): self
    {
        $this->roots[] = [
            'path' => rtrim($path, '/'),
            'url' => rtrim($url, '/'),
        ];
        return $this;
    }

    /**
     * Switch context and apply its default settings.
     * This clears any previously enqueued assets to prevent cross-context leaking.
     */
    public function setContext(string $context): self
    {
        if (isset($this->contextDefaults[$context])) {
            $this->mode = $this->contextDefaults[$context]['mode'];
        }
        // Clear enqueued assets when switching context
        $this->css = [];
        $this->js = [];
        return $this;
    }

    /**
     * Set the rendering mode manually
     */
    public function setMode(string $mode): self
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Pre-register an asset handle (WordPress style)
     */
    public function register(string $type, string $id, string $path, array $deps = [], array $options = []): self
    {
        $this->registry[$type][$id] = [
            'path' => $path,
            'deps' => $deps,
            'options' => $options
        ];
        return $this;
    }

    /**
     * Enqueue CSS asset
     */
    public function enqueueCss(string $id, ?string $path = null, array $deps = [], array $options = []): void
    {
        if ($path === null && isset($this->registry['css'][$id])) {
            $asset = $this->registry['css'][$id];
            $path = $asset['path'];
            if (empty($deps)) $deps = $asset['deps'];
            if (empty($options)) $options = $asset['options'];
        }

        $this->css[$id] = [
            'path' => $path ?: $id,
            'deps' => $deps,
            'options' => $options
        ];
    }

    /**
     * Enqueue JS asset
     */
    public function enqueueJs(string $id, ?string $path = null, array $deps = [], array $options = []): void
    {
        if ($path === null && isset($this->registry['js'][$id])) {
            $asset = $this->registry['js'][$id];
            $path = $asset['path'];
            if (empty($deps)) $deps = $asset['deps'];
            if (empty($options)) $options = $asset['options'];
        }

        $this->js[$id] = [
            'path' => $path ?: $id,
            'deps' => $deps,
            'options' => $options
        ];
    }

    /**
     * Render all enqueued CSS in correct dependency order
     */
    public function renderCss(): string
    {
        $sorted = $this->resolveDependencies($this->css);
        $html = '';
        
        foreach ($sorted as $id => $asset) {
            $info = $this->resolveAsset($asset['path']);
            if ($this->mode === 'internal') {
                $content = $this->getAssetContent($info['path']);
                $html .= "<!-- Asset: {$id} -->\n<style id=\"{$id}-inline\">\n{$content}\n</style>\n";
            } else {
                $url = $this->appendVersion($info['url'], $info['path']);
                $media = $asset['options']['media'] ?? 'all';
                $html .= "<link rel=\"stylesheet\" id=\"{$id}-css\" href=\"{$url}\" type=\"text/css\" media=\"{$media}\">\n";
            }
        }
        
        return $html;
    }

    /**
     * Render all enqueued JS in correct dependency order
     */
    public function renderJs(): string
    {
        $sorted = $this->resolveDependencies($this->js);
        $html = '';
        
        foreach ($sorted as $id => $asset) {
            $info = $this->resolveAsset($asset['path']);
            if ($this->mode === 'internal') {
                $content = $this->getAssetContent($info['path']);
                $html .= "<!-- Asset: {$id} -->\n<script id=\"{$id}-inline\">\n{$content}\n</script>\n";
            } else {
                $url = $this->appendVersion($info['url'], $info['path']);
                $async = !empty($asset['options']['async']) ? ' async' : '';
                $defer = !empty($asset['options']['defer']) ? ' defer' : '';
                $type = isset($asset['options']['type']) ? " type=\"{$asset['options']['type']}\"" : '';
                $html .= "<script src=\"{$url}\" id=\"{$id}-js\"{$async}{$defer}{$type}></script>\n";
            }
        }
        
        return $html;
    }

    /**
     * Resolve asset URL and file path through discovery roots
     */
    public function resolveAsset(string $path): array
    {
        // Absolute URLs pass through
        if (preg_match('/^https?:\/\//', $path)) {
            return [
                'path' => null,
                'url'  => $path,
            ];
        }

        $path = ltrim($path, '/');

        // Check manifest if available (only for public root currently)
        if ($this->manifest === null) {
            $this->loadManifest();
        }
        if (isset($this->manifest[$path])) {
            $path = ltrim($this->manifest[$path], '/');
        }

        // Search in discovery roots
        foreach (array_reverse($this->roots) as $root) {
            $fullPath = $root['path'] . '/' . $path;
            if (file_exists($fullPath)) {
                return [
                    'path' => $fullPath,
                    'url'  => $root['url'] . '/' . $path,
                ];
            }
        }

        // Fallback to default public
        return [
            'path' => $this->publicPath . '/' . $path,
            'url'  => $this->baseUrl . '/' . $path,
        ];
    }

    /**
     * Append versioning to URL
     */
    protected function appendVersion(string $url, ?string $fullPath): string
    {
        if ($fullPath && file_exists($fullPath)) {
            $v = substr(md5((string)filemtime($fullPath)), 0, 8);
            return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . $v;
        }
        return $url;
    }

    /**
     * Get content of a local asset file
     */
    protected function getAssetContent(?string $fullPath): string
    {
        if ($fullPath && file_exists($fullPath)) {
            return file_get_contents($fullPath);
        }
        return "/* Asset not found: {$fullPath} */";
    }

    /**
     * Simple Topological Sort for dependency resolution
     */
    protected function resolveDependencies(array $assets): array
    {
        $resolved = [];
        $unresolved = $assets;

        $resolve = function($id) use (&$resolved, &$unresolved, &$resolve) {
            if (!isset($unresolved[$id])) return;
            
            $asset = $unresolved[$id];
            unset($unresolved[$id]);

            foreach ($asset['deps'] as $depId) {
                $resolve($depId);
            }

            $resolved[$id] = $asset;
        };

        while (!empty($unresolved)) {
            $ids = array_keys($unresolved);
            $resolve(reset($ids));
        }

        return $resolved;
    }

    protected function loadManifest(): void
    {
        $this->manifest = [];
        $manifestPath = $this->publicPath . '/manifest.json';
        
        if (file_exists($manifestPath)) {
            $content = file_get_contents($manifestPath);
            $this->manifest = json_decode($content, true) ?: [];
        }
    }
}
