<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\View;

use PHPUnit\Framework\TestCase;
use Witals\Framework\View\View;
use Witals\Framework\Contracts\View\Engine;

class ViewTest extends TestCase
{
    private View $view;
    private Engine $engine;

    protected function setUp(): void
    {
        $this->engine = $this->createMock(Engine::class);
        $this->view = new View('test.view', '/path/to/view.php', ['key' => 'value'], $this->engine);
    }

    public function testConstructorSetsProperties(): void
    {
        $this->assertSame('test.view', $this->view->name());
        $this->assertSame(['key' => 'value'], $this->view->getData());
    }

    public function testRenderCallsEngine(): void
    {
        $this->engine->expects($this->once())
            ->method('get')
            ->with('/path/to/view.php', ['key' => 'value'])
            ->willReturn('rendered content');

        $result = $this->view->render();

        $this->assertSame('rendered content', $result);
    }

    public function testNameReturnsViewName(): void
    {
        $this->assertSame('test.view', $this->view->name());
    }

    public function testGetDataReturnsData(): void
    {
        $this->assertSame(['key' => 'value'], $this->view->getData());
    }

    public function testWithAddsSingleKey(): void
    {
        $result = $this->view->with('new_key', 'new_value');

        $this->assertSame($this->view, $result);
        $this->assertSame('new_value', $this->view->getData()['new_key']);
    }

    public function testWithAddsMultipleKeys(): void
    {
        $result = $this->view->with(['key1' => 'value1', 'key2' => 'value2']);

        $this->assertSame($this->view, $result);
        $this->assertSame('value1', $this->view->getData()['key1']);
        $this->assertSame('value2', $this->view->getData()['key2']);
    }

    public function testWithMergesData(): void
    {
        $this->view->with('new_key', 'new_value');

        $this->assertArrayHasKey('key', $this->view->getData());
        $this->assertArrayHasKey('new_key', $this->view->getData());
    }

    public function testToStringReturnsRenderedContent(): void
    {
        $this->engine->method('get')->willReturn('rendered content');

        $result = (string)$this->view;

        $this->assertSame('rendered content', $result);
    }

    public function testImplementsViewContract(): void
    {
        $this->assertInstanceOf(\Witals\Framework\Contracts\View\View::class, $this->view);
    }
}
