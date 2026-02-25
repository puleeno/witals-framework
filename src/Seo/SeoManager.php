<?php

declare(strict_types=1);

namespace Witals\Framework\Seo;

/**
 * Class SeoManager
 * 
 * Manages SEO metadata for the current request.
 */
class SeoManager
{
    protected array $data = [];

    public function set(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    public function get(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->data;
        }
        return $this->data[$key] ?? $default;
    }

    public function render(): string
    {
        $tags = [];
        
        $title = $this->get('title');
        if ($title) {
            $tags[] = "<title>" . htmlspecialchars($title) . "</title>";
            $tags[] = '<meta property="og:title" content="' . htmlspecialchars($title) . '">';
        }

        $description = $this->get('description');
        if ($description) {
            $tags[] = '<meta name="description" content="' . htmlspecialchars($description) . '">';
            $tags[] = '<meta property="og:description" content="' . htmlspecialchars($description) . '">';
        }

        $ogImage = $this->get('og_image');
        if ($ogImage) {
            $tags[] = '<meta property="og:image" content="' . htmlspecialchars($ogImage) . '">';
        }

        $canonical = $this->get('canonical');
        if ($canonical) {
            $tags[] = '<link rel="canonical" href="' . htmlspecialchars($canonical) . '">';
        }

        return implode("\n    ", $tags);
    }
}
