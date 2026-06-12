<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Seo\Traits;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Seo\Traits\HasSeo;

class HasSeoTest
{
    use HasSeo;

    public function testGetSeoDataFromJsonMetadata(): void
    {
        $row = [
            'seo_metadata' => json_encode([
                'title' => 'SEO Title',
                'description' => 'SEO Description',
                'keywords' => 'keyword1, keyword2',
                'og_image' => 'https://example.com/image.jpg',
                'canonical' => 'https://example.com/page'
            ])
        ];

        $seoData = $this->getSeoData($row);

        $this->assertSame('SEO Title', $seoData['title']);
        $this->assertSame('SEO Description', $seoData['description']);
        $this->assertSame('keyword1, keyword2', $seoData['keywords']);
        $this->assertSame('https://example.com/image.jpg', $seoData['og_image']);
        $this->assertSame('https://example.com/page', $seoData['canonical']);
    }

    public function testGetSeoDataFromArrayMetadata(): void
    {
        $row = [
            'seo_metadata' => [
                'title' => 'SEO Title',
                'description' => 'SEO Description'
            ]
        ];

        $seoData = $this->getSeoData($row);

        $this->assertSame('SEO Title', $seoData['title']);
        $this->assertSame('SEO Description', $seoData['description']);
    }

    public function testGetSeoDataFallsBackToName(): void
    {
        $row = ['name' => 'Product Name'];

        $seoData = $this->getSeoData($row);

        $this->assertSame('Product Name', $seoData['title']);
    }

    public function testGetSeoDataFallsBackToTitle(): void
    {
        $row = ['title' => 'Post Title'];

        $seoData = $this->getSeoData($row);

        $this->assertSame('Post Title', $seoData['title']);
    }

    public function testGetSeoDataFallsBackToDescription(): void
    {
        $row = ['description' => 'Post Description'];

        $seoData = $this->getSeoData($row);

        $this->assertSame('Post Description', $seoData['description']);
    }

    public function testGetSeoDataFallsBackToExcerpt(): void
    {
        $row = ['excerpt' => 'Post Excerpt'];

        $seoData = $this->getSeoData($row);

        $this->assertSame('Post Excerpt', $seoData['description']);
    }

    public function testGetSeoDataFallsBackToImageUrl(): void
    {
        $row = ['image_url' => 'https://example.com/image.jpg'];

        $seoData = $this->getSeoData($row);

        $this->assertSame('https://example.com/image.jpg', $seoData['og_image']);
    }

    public function testGetSeoDataFallsBackToThumbnail(): void
    {
        $row = ['thumbnail' => 'https://example.com/thumb.jpg'];

        $seoData = $this->getSeoData($row);

        $this->assertSame('https://example.com/thumb.jpg', $seoData['og_image']);
    }

    public function testGetSeoDataWithEmptyRow(): void
    {
        $row = [];

        $seoData = $this->getSeoData($row);

        $this->assertSame('', $seoData['title']);
        $this->assertSame('', $seoData['description']);
        $this->assertSame('', $seoData['keywords']);
        $this->assertNull($seoData['og_image']);
        $this->assertNull($seoData['canonical']);
    }

    public function testGetSeoDataWithNullMetadata(): void
    {
        $row = ['seo_metadata' => null];

        $seoData = $this->getSeoData($row);

        $this->assertSame('', $seoData['title']);
        $this->assertSame('', $seoData['description']);
    }

    public function testGetSeoDataWithInvalidJson(): void
    {
        $row = ['seo_metadata' => 'invalid json'];

        $seoData = $this->getSeoData($row);

        $this->assertSame('', $seoData['title']);
        $this->assertSame('', $seoData['description']);
    }

    public function testGetSeoDataWithLocale(): void
    {
        $row = [
            'seo_metadata' => json_encode([
                'title' => json_encode(['en' => 'English Title', 'vi' => 'Tiếng Việt']),
                'description' => 'Description'
            ])
        ];

        $seoData = $this->getSeoData($row, 'en');

        $this->assertSame('English Title', $seoData['title']);
    }
}
