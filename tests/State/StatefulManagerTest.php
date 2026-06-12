<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\State;

use PHPUnit\Framework\TestCase;
use Witals\Framework\State\StatefulManager;

class StatefulManagerTest extends TestCase
{
    private StatefulManager $manager;

    protected function setUp(): void
    {
        // Clear static state before each test
        $reflection = new \ReflectionClass(StatefulManager::class);
        $persistentState = $reflection->getProperty('persistentState');
        $persistentState->setAccessible(true);
        $persistentState->setValue(null, []);

        $persistentTtl = $reflection->getProperty('persistentTtl');
        $persistentTtl->setAccessible(true);
        $persistentTtl->setValue(null, []);

        $this->manager = new StatefulManager();
    }

    protected function tearDown(): void
    {
        // Clear static state after each test
        $reflection = new \ReflectionClass(StatefulManager::class);
        $persistentState = $reflection->getProperty('persistentState');
        $persistentState->setAccessible(true);
        $persistentState->setValue(null, []);

        $persistentTtl = $reflection->getProperty('persistentTtl');
        $persistentTtl->setAccessible(true);
        $persistentTtl->setValue(null, []);
    }

    public function testSetStoresRequestState(): void
    {
        $this->manager->set('key', 'value');

        $this->assertSame('value', $this->manager->get('key'));
    }

    public function testSetPersistentStoresPersistentState(): void
    {
        $this->manager->setPersistent('key', 'value');

        $this->assertSame('value', $this->manager->getPersistent('key'));
    }

    public function testSetPersistentWithTtl(): void
    {
        $this->manager->setPersistent('key', 'value', 1);

        $this->assertSame('value', $this->manager->getPersistent('key'));

        sleep(2);

        $this->assertNull($this->manager->getPersistent('key'));
    }

    public function testGetReturnsRequestStateFirst(): void
    {
        $this->manager->set('key', 'request_value');
        $this->manager->setPersistent('key', 'persistent_value');

        $this->assertSame('request_value', $this->manager->get('key'));
    }

    public function testGetReturnsPersistentStateWhenRequestStateNotSet(): void
    {
        $this->manager->setPersistent('key', 'persistent_value');

        $this->assertSame('persistent_value', $this->manager->get('key'));
    }

    public function testGetReturnsDefaultWhenNeitherSet(): void
    {
        $result = $this->manager->get('nonexistent', 'default');

        $this->assertSame('default', $result);
    }

    public function testGetPersistentReturnsPersistentState(): void
    {
        $this->manager->setPersistent('key', 'value');

        $this->assertSame('value', $this->manager->getPersistent('key'));
    }

    public function testGetPersistentReturnsDefaultWhenNotSet(): void
    {
        $result = $this->manager->getPersistent('nonexistent', 'default');

        $this->assertSame('default', $result);
    }

    public function testHasReturnsTrueForRequestState(): void
    {
        $this->manager->set('key', 'value');

        $this->assertTrue($this->manager->has('key'));
    }

    public function testHasReturnsTrueForPersistentState(): void
    {
        $this->manager->setPersistent('key', 'value');

        $this->assertTrue($this->manager->has('key'));
    }

    public function testHasReturnsFalseWhenNotSet(): void
    {
        $this->assertFalse($this->manager->has('nonexistent'));
    }

    public function testHasPersistentReturnsTrueForPersistentState(): void
    {
        $this->manager->setPersistent('key', 'value');

        $this->assertTrue($this->manager->hasPersistent('key'));
    }

    public function testHasPersistentReturnsFalseForRequestState(): void
    {
        $this->manager->set('key', 'value');

        $this->assertFalse($this->manager->hasPersistent('key'));
    }

    public function testForgetRemovesFromBothStates(): void
    {
        $this->manager->set('key', 'request_value');
        $this->manager->setPersistent('key', 'persistent_value');
        $this->manager->forget('key');

        $this->assertFalse($this->manager->has('key'));
        $this->assertFalse($this->manager->hasPersistent('key'));
    }

    public function testForgetRequestRemovesOnlyFromRequestState(): void
    {
        $this->manager->set('key', 'request_value');
        $this->manager->setPersistent('key', 'persistent_value');
        $this->manager->forgetRequest('key');

        $this->assertFalse($this->manager->has('key'));
        $this->assertTrue($this->manager->hasPersistent('key'));
    }

    public function testForgetPersistentRemovesOnlyFromPersistentState(): void
    {
        $this->manager->set('key', 'request_value');
        $this->manager->setPersistent('key', 'persistent_value');
        $this->manager->forgetPersistent('key');

        $this->assertTrue($this->manager->has('key'));
        $this->assertFalse($this->manager->hasPersistent('key'));
    }

    public function testClearEmptiesRequestState(): void
    {
        $this->manager->set('key1', 'value1');
        $this->manager->set('key2', 'value2');
        $this->manager->setPersistent('persistent_key', 'persistent_value');
        $this->manager->clear();

        $this->assertEmpty($this->manager->allRequest());
        $this->assertTrue($this->manager->hasPersistent('persistent_key'));
    }

    public function testClearPersistentEmptiesPersistentState(): void
    {
        $this->manager->set('key', 'value');
        $this->manager->setPersistent('persistent_key', 'persistent_value');
        $this->manager->clearPersistent();

        $this->assertTrue($this->manager->has('key'));
        $this->assertFalse($this->manager->hasPersistent('persistent_key'));
    }

    public function testClearAllEmptiesBothStates(): void
    {
        $this->manager->set('key', 'value');
        $this->manager->setPersistent('persistent_key', 'persistent_value');
        $this->manager->clearAll();

        $this->assertFalse($this->manager->has('key'));
        $this->assertFalse($this->manager->hasPersistent('persistent_key'));
    }

    public function testAllReturnsBothStates(): void
    {
        $this->manager->set('request_key', 'request_value');
        $this->manager->setPersistent('persistent_key', 'persistent_value');

        $all = $this->manager->all();

        $this->assertArrayHasKey('request_key', $all);
        $this->assertArrayHasKey('persistent_key', $all);
    }

    public function testAllRequestReturnsOnlyRequestState(): void
    {
        $this->manager->set('request_key', 'request_value');
        $this->manager->setPersistent('persistent_key', 'persistent_value');

        $requestState = $this->manager->allRequest();

        $this->assertArrayHasKey('request_key', $requestState);
        $this->assertArrayNotHasKey('persistent_key', $requestState);
    }

    public function testAllPersistentReturnsOnlyPersistentState(): void
    {
        $this->manager->set('request_key', 'request_value');
        $this->manager->setPersistent('persistent_key', 'persistent_value');

        $persistentState = $this->manager->allPersistent();

        $this->assertArrayNotHasKey('request_key', $persistentState);
        $this->assertArrayHasKey('persistent_key', $persistentState);
    }

    public function testIsStatefulReturnsTrue(): void
    {
        $this->assertTrue($this->manager->isStateful());
    }

    public function testAfterRequestClearsRequestState(): void
    {
        $this->manager->set('key', 'value');
        $this->manager->setPersistent('persistent_key', 'persistent_value');
        $this->manager->afterRequest();

        $this->assertFalse($this->manager->has('key'));
        $this->assertTrue($this->manager->hasPersistent('persistent_key'));
    }

    public function testGetStatsReturnsStatistics(): void
    {
        $this->manager->set('key1', 'value1');
        $this->manager->setPersistent('persistent_key', 'persistent_value');

        $stats = $this->manager->getStats();

        $this->assertArrayHasKey('request_state_count', $stats);
        $this->assertArrayHasKey('persistent_state_count', $stats);
        $this->assertArrayHasKey('request_memory', $stats);
        $this->assertArrayHasKey('persistent_memory', $stats);
        $this->assertArrayHasKey('total_memory', $stats);
        $this->assertSame(1, $stats['request_state_count']);
        $this->assertSame(1, $stats['persistent_state_count']);
    }

    public function testGarbageCollectRemovesExpiredEntries(): void
    {
        $this->manager->setPersistent('key1', 'value1', 1);
        $this->manager->setPersistent('key2', 'value2', 100);

        sleep(2);
        $this->manager->afterRequest();

        $this->assertFalse($this->manager->hasPersistent('key1'));
        $this->assertTrue($this->manager->hasPersistent('key2'));
    }

    public function testImplementsStateManagerInterface(): void
    {
        $this->assertInstanceOf(\Witals\Framework\Contracts\StateManager::class, $this->manager);
    }
}
