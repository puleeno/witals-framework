<?php

declare(strict_types=1);

namespace Witals\Framework\Seo\Contracts;

/**
 * Interface SeoableInterface
 * 
 * Defines the structure for items that have SEO metadata.
 */
interface SeoableInterface
{
    public function getSeoTitle(): ?string;
    public function getSeoDescription(): ?string;
    public function getSeoKeywords(): ?array;
    public function getOgImage(): ?string;
    public function getCanonicalUrl(): ?string;
}
