<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Context;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Context\ContextLoader;
use Witals\Framework\Context\Contracts\BlockInterface;
use Witals\Framework\Context\Contracts\BlockManagerInterface;
use Witals\Framework\Context\Contracts\ContextInterface;
use Witals\Framework\Context\Contracts\ContextLoaderInterface;
use Witals\Framework\Http\Response;
use SimpleXMLElement;

class ContextLoaderRenderingTest extends TestCase
{
    private BlockManagerInterface $blockManager;
    private ContextLoader $loader;

    protected function setUp(): void
    {
        $this->blockManager = $this->createMock(BlockManagerInterface::class);
        $this->loader = new ContextLoader($this->blockManager);
    }

    public function test_interface_defines_three_format_constants(): void
    {
        $this->assertSame('html', ContextLoaderInterface::FORMAT_HTML);
        $this->assertSame('json', ContextLoaderInterface::FORMAT_JSON);
        $this->assertSame('xml', ContextLoaderInterface::FORMAT_XML);
    }

    public function test_load_defaults_to_html(): void
    {
        $context = $this->makeContext(['@core/html' => ['content' => '<p>Hello</p>']]);

        $response = $this->loader->load($context);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('text/html', $response->getHeader('Content-Type'));
        $this->assertStringContainsString('Hello', $response->getContent());
    }

    public function test_load_html_returns_full_document(): void
    {
        $context = $this->makeContext(['@core/html' => ['content' => '<p>Hello</p>']]);

        $response = $this->loader->load($context, ContextLoaderInterface::FORMAT_HTML);

        $this->assertStringContainsString('text/html', $response->getHeader('Content-Type'));
        $this->assertStringContainsString('<!DOCTYPE html>', $response->getContent());
        $this->assertStringContainsString('<p>Hello</p>', $response->getContent());
        $this->assertStringContainsString('<title>', $response->getContent());
    }

    public function test_load_json_returns_application_json(): void
    {
        $context = $this->makeContext(['@core/html' => ['content' => '<p>Data</p>']]);

        $response = $this->loader->load($context, ContextLoaderInterface::FORMAT_JSON);

        $this->assertSame('application/json', $response->getHeader('Content-Type'));
    }

    public function test_load_json_response_contains_context_structure(): void
    {
        $block = $this->makeBlock('core/paragraph', 'Paragraph', 'text');
        $context = $this->makeContextWithBlocks([
            ['blockId' => 'core/paragraph', 'attributes' => ['content' => 'Hi'], 'mode' => 'ssr', 'children' => []],
        ]);

        $this->blockManager->method('getBlock')->with('core/paragraph')->willReturn($block);

        $response = $this->loader->load($context, ContextLoaderInterface::FORMAT_JSON);
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('context', $data);
        $this->assertArrayHasKey('metadata', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('blocks', $data);
        $this->assertSame('test_page', $data['context']['type']);
        $this->assertSame('home', $data['context']['identifier']);
        $this->assertSame('Test', $data['context']['label']);
        $this->assertSame('Desc', $data['context']['description']);
    }

    public function test_load_json_block_tree_serialization(): void
    {
        $block = $this->makeBlock('core/paragraph', 'Paragraph', 'text');
        $this->blockManager->method('getBlock')->with('core/paragraph')->willReturn($block);

        $context = $this->makeContextWithBlocks([
            [
                'blockId' => 'core/paragraph',
                'attributes' => ['content' => 'Hi'],
                'mode' => 'ssr',
                'children' => [
                    ['blockId' => '@core/html', 'attributes' => ['content' => '<span>Nested</span>'], 'mode' => 'ssr', 'children' => []],
                ],
            ],
        ]);

        $response = $this->loader->load($context, ContextLoaderInterface::FORMAT_JSON);
        $data = json_decode($response->getContent(), true);

        $this->assertCount(1, $data['blocks']);
        $this->assertSame('core/paragraph', $data['blocks'][0]['type']);
        $this->assertSame('ssr', $data['blocks'][0]['mode']);
        $this->assertSame('Paragraph', $data['blocks'][0]['name']);
        $this->assertSame('text', $data['blocks'][0]['category']);
        $this->assertCount(1, $data['blocks'][0]['children']);
        $this->assertSame('html', $data['blocks'][0]['children'][0]['type']);
        $this->assertSame('<span>Nested</span>', $data['blocks'][0]['children'][0]['content']);
    }

    public function test_load_xml_returns_application_xml(): void
    {
        $context = $this->makeContext([]);

        $response = $this->loader->load($context, ContextLoaderInterface::FORMAT_XML);

        $this->assertSame('application/xml', $response->getHeader('Content-Type'));
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $response->getContent());
        $this->assertStringContainsString('<response>', $response->getContent());
    }

    public function test_load_xml_contains_valid_xml(): void
    {
        $block = $this->makeBlock('core/heading', 'Heading', 'text');
        $this->blockManager->method('getBlock')->with('core/heading')->willReturn($block);

        $context = $this->makeContextWithBlocks([
            ['blockId' => 'core/heading', 'attributes' => ['level' => 2, 'content' => 'Title'], 'mode' => 'ssr', 'children' => []],
        ]);

        $response = $this->loader->load($context, ContextLoaderInterface::FORMAT_XML);
        $xml = $response->getContent();

        $parsed = new SimpleXMLElement($xml);
        $this->assertNotNull($parsed->context);
        $this->assertSame('test_page', (string) $parsed->context->type);
        $this->assertSame('home', (string) $parsed->context->identifier);

        $this->assertNotNull($parsed->blocks);
        $this->assertNotNull($parsed->blocks->item);
        $this->assertSame('core/heading', (string) $parsed->blocks->item->type);
    }

    public function test_to_array_returns_expected_structure(): void
    {
        $context = $this->makeContext(['@core/html' => ['content' => '<p>Test</p>']]);

        $result = $this->loader->toArray($context);

        $this->assertIsArray($result);
        $this->assertSame('test_page', $result['context']['type']);
        $this->assertSame('home', $result['context']['identifier']);
        $this->assertSame('Desc', $result['context']['description']);
        $this->assertSame(['theme' => 'dark'], $result['metadata']);
        $this->assertSame(['post_id' => 1], $result['data']);
        $this->assertCount(1, $result['blocks']);
    }

    public function test_to_json_returns_valid_json(): void
    {
        $context = $this->makeContext([]);

        $json = $this->loader->toJson($context);

        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertSame('test_page', $data['context']['type']);
    }

    public function test_to_xml_returns_valid_xml(): void
    {
        $context = $this->makeContext([]);

        $xml = $this->loader->toXml($context);

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $parsed = new SimpleXMLElement($xml);
        $this->assertSame('test_page', (string) $parsed->context->type);
    }

    public function test_to_xml_escapes_special_characters(): void
    {
        $metadata = ['title' => 'AT&T "Widgets" <Test>'];
        $context = $this->createMock(ContextInterface::class);
        $context->method('getType')->willReturn('page');
        $context->method('getIdentifier')->willReturn('test');
        $context->method('getLabel')->willReturn('Test');
        $context->method('getDescription')->willReturn('');
        $context->method('getMetadata')->willReturn($metadata);
        $context->method('getData')->willReturn([]);
        $context->method('getBlockTree')->willReturn([]);

        $xml = $this->loader->toXml($context);

        $parsed = new SimpleXMLElement($xml);
        $this->assertSame('AT&T "Widgets" <Test>', (string) $parsed->metadata->title);
    }

    public function test_json_response_format_preserves_block_mode(): void
    {
        $block = $this->makeBlock('core/image', 'Image', 'media');
        $this->blockManager->method('getBlock')->with('core/image')->willReturn($block);

        $context = $this->makeContextWithBlocks([
            ['blockId' => 'core/image', 'attributes' => ['src' => 'a.jpg'], 'mode' => 'csr', 'children' => []],
        ]);

        $response = $this->loader->load($context, ContextLoaderInterface::FORMAT_JSON);
        $data = json_decode($response->getContent(), true);

        $this->assertSame('csr', $data['blocks'][0]['mode']);
    }

    public function test_direct_response_bypasses_context(): void
    {
        $response = Response::json(['posts' => [['id' => 1]]]);

        $this->assertSame('application/json', $response->getHeader('Content-Type'));
        $body = json_decode($response->getContent(), true);
        $this->assertSame(1, $body['posts'][0]['id']);
    }

    public function test_api_controller_returns_structured_response(): void
    {
        $response = Response::json([
            'id' => 42,
            'title' => 'Hello World',
        ]);

        $this->assertSame('application/json', $response->getHeader('Content-Type'));
        $data = json_decode($response->getContent(), true);
        $this->assertSame(42, $data['id']);
        $this->assertSame('Hello World', $data['title']);
    }

    public function test_multiple_blocks_in_json(): void
    {
        $heading = $this->makeBlock('core/heading', 'Heading', 'text');
        $paragraph = $this->makeBlock('core/paragraph', 'Paragraph', 'text');
        $image = $this->makeBlock('core/image', 'Image', 'media');

        $this->blockManager->method('getBlock')->willReturnMap([
            ['core/heading', $heading],
            ['core/paragraph', $paragraph],
            ['core/image', $image],
        ]);

        $context = $this->makeContextWithBlocks([
            ['blockId' => 'core/heading', 'attributes' => ['content' => 'Title'], 'mode' => 'ssr', 'children' => []],
            ['blockId' => 'core/paragraph', 'attributes' => ['content' => 'Body'], 'mode' => 'ssr', 'children' => []],
            ['blockId' => 'core/image', 'attributes' => ['src' => 'img.jpg'], 'mode' => 'hybrid', 'children' => []],
        ]);

        $result = $this->loader->toArray($context);

        $this->assertCount(3, $result['blocks']);
        $this->assertSame('core/heading', $result['blocks'][0]['type']);
        $this->assertSame('core/paragraph', $result['blocks'][1]['type']);
        $this->assertSame('core/image', $result['blocks'][2]['type']);
        $this->assertSame('hybrid', $result['blocks'][2]['mode']);
    }

    public function test_html_block_serialization(): void
    {
        $context = $this->makeContextWithBlocks([
            ['blockId' => '@core/html', 'attributes' => ['content' => '<div>Raw HTML</div>'], 'mode' => 'ssr', 'children' => []],
        ]);

        $result = $this->loader->toArray($context);

        $this->assertSame('html', $result['blocks'][0]['type']);
        $this->assertSame('<div>Raw HTML</div>', $result['blocks'][0]['content']);
    }

    public function test_unknown_block_omits_name_and_category(): void
    {
        $this->blockManager->method('getBlock')->with('core/unknown')->willReturn(null);

        $context = $this->makeContextWithBlocks([
            ['blockId' => 'core/unknown', 'attributes' => [], 'mode' => 'ssr', 'children' => []],
        ]);

        $result = $this->loader->toArray($context);

        $this->assertSame('core/unknown', $result['blocks'][0]['type']);
        $this->assertArrayNotHasKey('name', $result['blocks'][0]);
        $this->assertArrayNotHasKey('category', $result['blocks'][0]);
    }

    public function test_empty_block_tree_returns_empty_blocks(): void
    {
        $context = $this->makeContext([]);

        $result = $this->loader->toArray($context);

        $this->assertSame([], $result['blocks']);
    }

    private function makeContext(array $blockTreeAttrs): ContextInterface
    {
        $tree = [];
        foreach ($blockTreeAttrs as $blockId => $attrs) {
            $tree[] = [
                'blockId' => $blockId,
                'attributes' => $attrs,
                'mode' => 'ssr',
                'children' => [],
            ];
        }

        return $this->makeContextWithBlocks($tree);
    }

    private function makeContextWithBlocks(array $blockTree): ContextInterface
    {
        $context = $this->createMock(ContextInterface::class);
        $context->method('getType')->willReturn('test_page');
        $context->method('getIdentifier')->willReturn('home');
        $context->method('getLabel')->willReturn('Test');
        $context->method('getDescription')->willReturn('Desc');
        $context->method('getData')->willReturn(['post_id' => 1]);
        $context->method('getMetadata')->willReturn(['theme' => 'dark']);
        $context->method('getBlockTree')->willReturn($blockTree);
        $context->method('getTemplate')->willReturn(null);

        return $context;
    }

    private function makeBlock(string $id, string $name, string $category): BlockInterface
    {
        $block = $this->createMock(BlockInterface::class);
        $block->method('getId')->willReturn($id);
        $block->method('getName')->willReturn($name);
        $block->method('getCategory')->willReturn($category);
        $block->method('getDescription')->willReturn('');
        $block->method('getScope')->willReturn(BlockInterface::SCOPE_GLOBAL);
        $block->method('getDefaultAttributes')->willReturn([]);
        $block->method('getDefaultRenderMode')->willReturn(BlockInterface::MODE_SSR);
        $block->method('getStyles')->willReturn([]);
        $block->method('getScripts')->willReturn([]);
        $block->method('getSupports')->willReturn([]);

        return $block;
    }
}
