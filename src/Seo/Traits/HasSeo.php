<?php

declare(strict_types=1);

namespace Witals\Framework\Seo\Traits;

use Witals\Framework\Database\Traits\Translatable;

/**
 * Trait HasSeo
 * 
 * Provides utility for handling SEO fields, integrating with 
 * multilingual support if available.
 */
trait HasSeo
{
    use Translatable;

    /**
     * Resolve SEO data from a database row.
     * Expects an 'seo_metadata' JSON column or similar.
     */
    public function getSeoData(array $row, ?string $locale = null): array
    {
        $metadata = [];
        if (isset($row['seo_metadata'])) {
            $metadata = is_string($row['seo_metadata']) 
                ? json_decode($row['seo_metadata'], true) 
                : $row['seo_metadata'];
        }

        return [
            'title'       => $this->translate($metadata['title'] ?? ($row['name'] ?? $row['title'] ?? ''), $locale),
            'description' => $this->translate($metadata['description'] ?? ($row['description'] ?? $row['excerpt'] ?? ''), $locale),
            'keywords'    => $this->translate($metadata['keywords'] ?? '', $locale),
            'og_image'    => $metadata['og_image'] ?? $row['image_url'] ?? $row['thumbnail'] ?? null,
            'canonical'   => $metadata['canonical'] ?? null,
        ];
    }
}
