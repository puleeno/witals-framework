<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Seo;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Seo\SeoManager;

class SeoManagerTest extends TestCase
{
    private SeoManager $seoManager;

    protected function setUp(): void
    {
        $this->seoManager = new SeoManager();
    }

    public function testSetMergesData(): void
    {
        $this->seoManager->set(['title' => 'Test Title']);
        $this->seoManager->set(['description' => 'Test Description']);

        $this->assertSame('Test Title', $this->seoManager->get('title'));
        $this->assertSame('Test Description', $this->seoManager->get('description'));
    }

    public function testSetOverwritesExistingKey(): void
    {
        $this->seoManager->set(['title' => 'Original']);
        $this->seoManager->set(['title' => 'Updated']);

        $this->assertSame('Updated', $this->seoManager->get('title'));
    }

    public function testGetReturnsAllDataWhenKeyIsNull(): void
    {
        $this->seoManager->set(['title' => 'Test', 'description' => 'Desc']);

        $data = $this->seoManager->get();

        $this->assertSame(['title' => 'Test', 'description' => 'Desc'], $data);
    }

    public function testGetReturnsSpecificKey(): void
    {
        $this->seoManager->set(['title' => 'Test Title']);

        $this->assertSame('Test Title', $this->seoManager->get('title'));
    }

    public function testGetReturnsDefaultWhenKeyNotFound(): void
    {
        $result = $this->seoManager->get('nonexistent', 'default');

        $this->assertSame('default', $result);
    }

    public function testGetReturnsNullWhenKeyNotFoundAndNoDefault(): void
    {
        $result = $this->seoManager->get('nonexistent');

        $this->assertNull($result);
    }

    public function testRenderWithTitle(): void
    {
        $this->seoManager->set(['title' => 'Test Title']);

        $result = $this->seoManager->render();

        $this->assertStringContainsString('<title>Test Title</title>', $result);
        $this->assertStringContainsString('og:title', $result);
    }

    public function testRenderEscapesTitle(): void
    {
        $this->seoManager->set(['title' => '<script>alert("XSS")</script>']);

        $result = $this->seoManager->render();

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testRenderWithDescription(): void
    {
        $this->seoManager->set(['description' => 'Test Description']);

        $result = $this->seoManager->render();

        $this->assertStringContainsString('name="description"', $result);
        $this->assertStringContainsString('Test Description', $result);
        $this->assertStringContainsString('og:description', $result);
    }

    public function testRenderEscapesDescription(): void
    {
        $this->seoManager->set(['description' => '<script>alert("XSS")</script>']);

        $result = $this->seoManager->render();

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testRenderWithOgImage(): void
    {
        $this->seoManager->set(['og_image' => 'https://example.com/image.jpg']);

        $result = $this->seoManager->render();

        $this->assertStringContainsString('og:image', $result);
        $this->assertStringContainsString('https://example.com/image.jpg', $result);
    }

    public function testRenderEscapesOgImage(): void
    {
        $this->seoManager->set(['og_image' => 'https://example.com/image.jpg" onerror="alert(1)']);

        $result = $this->seoManager->render();

        $this->assertStringNotContainsString('onerror', $result);
    }

    public function testRenderWithCanonical(): void
    {
        $this->seoManager->set(['canonical' => 'https://example.com/page']);

        $result = $this->seoManager->render();

        $this->assertStringContainsString('rel="canonical"', $result);
        $this->assertStringContainsString('https://example.com/page', $result);
    }

    public function testRenderEscapesCanonical(): void
    {
        $this->seoManager->set(['canonical' => 'https://example.com/page" onclick="alert(1)']);

        $result = $this->seoManager->render();

        $this->assertStringNotContainsString('onclick', $result);
    }

    public function testRenderWithAllFields(): void
    {
        $this->seoManager->set([
            'title' => 'Test Title',
            'description' => 'Test Description',
            'og_image' => 'https://example.com/image.jpg',
            'canonical' => 'https://example.com/page'
        ]);

        $result = $this->seoManager->render();

        $this->assertStringContainsString('<title>Test Title</title>', $result);
        $this->assertStringContainsString('description', $result);
        $this->assertStringContainsString('og:image', $result);
        $this->assertStringContainsString('canonical', $result);
    }

    public function testRenderWithEmptyData(): void
    {
        $result = $this->seoManager->render();

        $this->assertEmpty($result);
    }

    public function testRenderWithPartialData(): void
    {
        $this->seoManager->set(['title' => 'Test Title']);

        $result = $this->seoManager->render();

        $this->assertStringContainsString('<title>Test Title</title>', $result);
        $this->assertStringNotContainsString('description', $result);
    }
}
