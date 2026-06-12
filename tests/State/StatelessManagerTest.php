<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\State;

use PHPUnit\Framework\TestCase;
use Witals\Framework\State\StatelessManager;

class StatelessManagerTest extends TestCase
{
    private StatelessManager $manager;

    protected function setUp(): void
    {
        $this->manager = new StatelessManager();
    }

    public function testSetStoresValue(): void
    {
        $this->manager->set('key', 'value');

        $this->assertSame('value', $this->manager->get('key'));
    }

    public function testGetReturnsValue(): void
    {
        $this->manager->set('key', 'value');

        $this->assertSame('value', $this->manager->get('key'));
    }

    public function testGetReturnsDefaultWhenKeyNotFound(): void
    {
        $result = $this->manager->get('nonexistent', 'default');

        $this->assertSame('default', $result);
    }

    public function testGetReturnsNullWhenKeyNotFoundAndNoDefault(): void
    {
        $result = $this->manager->get('nonexistent');

        $this->assertNull($result);
    }

    public function testHasReturnsTrueWhenKeyExists(): void
    {
        $this->manager->set('key', 'value');

        $this->assertTrue($this->manager->has('key'));
    }

    public function testHasReturnsFalseWhenKeyNotExists(): void
    {
        $this->assertFalse($this->manager->has('nonexistent'));
    }

    public function testForgetKey(): void
    {
        $this->manager->set('key', 'value');
        $this->manager->forget('key');

        $this->assertFalse($this->manager->has('key'));
    }

    public function testClearEmptiesState(): void
    {
        $this->manager->set('key1', 'value1');
        $this->manager->set('key2', 'value2');
        $this->manager->clear();

        $this->assertEmpty($this->manager->all());
    }

    public function testAllReturnsAllState(): void
    {
        $this->manager->set('key1', 'value1');
        $this->manager->set('key2', 'value2');

        $all = $this->manager->all();

        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $all);
    }

    public function testIsStatefulReturnsFalse(): void
    {
        $this->assertFalse($this->manager->isStateful());
    }

    public function testSetPersistentBehavesLikeSet(): void
    {
        $this->manager->setPersistent('key', 'value');

        $this->assertSame('value', $this->manager->get('key'));
    }

    public function testGetPersistentBehavesLikeGet(): void
    {
        $this->manager->set('key', 'value');

        $this->assertSame('value', $this->manager->getPersistent('key'));
    }

    public function testGetStatsReturnsStatistics(): void
    {
        $this->manager->set('key1', 'value1');
        $this->manager->set('key2', 'value2');

        $stats = $this->manager->getStats();

        $this->assertArrayHasKey('request_state_count', $stats);
        $this->assertArrayHasKey('persistent_state_count', $stats);
        $this->assertArrayHasKey('total_memory', $stats);
        $this->assertSame(2, $stats['request_state_count']);
        $this->assertSame(0, $stats['persistent_state_count']);
    }

    public function testDestructClearsState(): void
    {
        $this->manager->set('key', 'value');

        unset($this->manager);

        $this->expectNotToPerformAssertions();
    }

    public function testMultipleOperations(): void
    {
        $this->manager->set('key1', 'value1');
        $this->manager->set('key2', 'value2');

        $this->assertTrue($this->manager->has('key1'));
        $this->assertTrue($this->manager->has('key2'));

        $this->manager->forget('key1');

        $this->assertFalse($this->manager->has('key1'));
        $this->assertTrue($this->manager->has('key2'));
    }

    public function testImplementsStateManagerInterface(): void
    {
        $this->assertInstanceOf(\Witals\Framework\Contracts\StateManager::class, $this->manager);
    }
}
