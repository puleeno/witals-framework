<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Unit\Http\Mappings;

use PHPUnit\Framework\TestCase;
use App\Http\Mappings\ConfigMappingPolicy;

class ConfigMappingPolicyTest extends TestCase
{
    public function test_exact_match_takes_precedence_over_wildcard(): void
    {
        $policy = new ConfigMappingPolicy([
            '/search' => 'search-page',
            '/search/*' => 'search-sub',
        ]);

        $this->assertSame('search-page', $policy->match('/search'));
        $this->assertSame('search-sub', $policy->match('/search/products'));
    }

    public function test_exact_match_takes_precedence_over_prefix(): void
    {
        $policy = new ConfigMappingPolicy([
            '/blog' => 'blog-page',
            '/blog/new' => 'blog-new',
        ]);

        $this->assertSame('blog-page', $policy->match('/blog'));
        $this->assertSame('blog-new', $policy->match('/blog/new'));
        $this->assertSame('blog-page', $policy->match('/blog/archive'));
    }

    public function test_root_only_does_not_match_subpaths(): void
    {
        $policy = new ConfigMappingPolicy([
            '/' => 'home',
        ]);

        $this->assertSame('home', $policy->match('/'));
        $this->assertSame('index', $policy->match('/about')); // default
    }

    public function test_overlapping_prefixes_resolve_correctly(): void
    {
        $policy = new ConfigMappingPolicy([
            '/products' => 'products-list',
            '/products/category' => 'products-category',
            '/products/category/*' => 'products-category-sub',
        ]);

        $this->assertSame('products-list', $policy->match('/products'));
        $this->assertSame('products-category', $policy->match('/products/category'));
        $this->assertSame('products-category-sub', $policy->match('/products/category/electronics'));
        $this->assertSame('products-list', $policy->match('/products/featured'));
    }

    public function test_wildcard_without_exact_match(): void
    {
        $policy = new ConfigMappingPolicy([
            '/category/*' => 'archive',
        ]);

        $this->assertSame('archive', $policy->match('/category/tech'));
        $this->assertSame('archive', $policy->match('/category/'));
        $this->assertSame('index', $policy->match('/other'));
    }

    public function test_no_default_returns_null(): void
    {
        $policy = new ConfigMappingPolicy([], '');
        $this->assertSame('', $policy->match('/anything'));
    }

    public function test_empty_mapping_uses_default(): void
    {
        $policy = new ConfigMappingPolicy([], 'fallback');
        $this->assertSame('fallback', $policy->match('/'));
        $this->assertSame('fallback', $policy->match('/any'));
    }

    public function test_longest_prefix_does_not_conflict(): void
    {
        $policy = new ConfigMappingPolicy([
            '/a' => 'a-page',
            '/a/b' => 'ab-page',
            '/a/b/c' => 'abc-page',
        ]);

        $this->assertSame('a-page', $policy->match('/a'));
        $this->assertSame('ab-page', $policy->match('/a/b'));
        $this->assertSame('abc-page', $policy->match('/a/b/c'));
        $this->assertSame('ab-page', $policy->match('/a/b/d'));
        $this->assertSame('a-page', $policy->match('/a/x'));
    }
}
