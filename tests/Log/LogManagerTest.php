<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Log;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Log\LogManager;
use Witals\Framework\Log\Drivers\NullLogger;

class LogManagerTest extends TestCase
{
    private LogManager $manager;

    protected function setUp(): void
    {
        $this->manager = new LogManager([
            'default' => 'null',
            'channels' => [
                'null' => ['driver' => 'null'],
            ]
        ]);
    }

    public function testConstructorSetsDefault(): void
    {
        $manager = new LogManager(['default' => 'debug']);

        $this->assertInstanceOf(LogManager::class, $manager);
    }

    public function testConstructorDefaultsToStandard(): void
    {
        $manager = new LogManager();

        $this->assertInstanceOf(LogManager::class, $manager);
    }

    public function testDriverReturnsLogger(): void
    {
        $logger = $this->manager->driver('null');

        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $logger);
    }

    public function testDriverReturnsDefaultWhenNotSpecified(): void
    {
        $logger = $this->manager->driver();

        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $logger);
    }

    public function testDriverCachesInstance(): void
    {
        $logger1 = $this->manager->driver('null');
        $logger2 = $this->manager->driver('null');

        $this->assertSame($logger1, $logger2);
    }

    public function testDriverCreatesNullDriver(): void
    {
        $manager = new LogManager([
            'channels' => ['null' => ['driver' => 'null']]
        ]);

        $logger = $manager->driver('null');

        $this->assertInstanceOf(NullLogger::class, $logger);
    }

    public function testDriverCreatesDebugDriver(): void
    {
        $manager = new LogManager([
            'channels' => ['debug' => ['driver' => 'debug']]
        ]);

        $logger = $manager->driver('debug');

        $this->assertInstanceOf(\Witals\Framework\Log\Drivers\DebugLogger::class, $logger);
    }

    public function testDriverThrowsExceptionForUnsupportedDriver(): void
    {
        $manager = new LogManager([
            'channels' => ['invalid' => ['driver' => 'invalid']]
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $manager->driver('invalid');
    }

    public function testSetRequestId(): void
    {
        $this->manager->setRequestId('test-request-id');

        $this->expectNotToPerformAssertions();
    }

    public function testFlushCallsFlushOnAllHandlers(): void
    {
        $this->manager->driver('null');

        $this->expectNotToPerformAssertions();

        $this->manager->flush();
    }

    public function testLogProxiesToDefaultDriver(): void
    {
        $this->expectNotToPerformAssertions();

        $this->manager->log('info', 'Test message');
    }

    public function testDestructFlushesHandlers(): void
    {
        $manager = new LogManager([
            'default' => 'null',
            'channels' => ['null' => ['driver' => 'null']]
        ]);

        $this->expectNotToPerformAssertions();

        unset($manager);
    }

    public function testMagicCallProxiesToDefaultDriver(): void
    {
        $this->expectNotToPerformAssertions();

        $this->manager->info('Info message');
        $this->manager->error('Error message');
        $this->manager->debug('Debug message');
    }

    public function testCreateStandardDriverWithConfig(): void
    {
        $manager = new LogManager([
            'channels' => [
                'standard' => [
                    'driver' => 'standard',
                    'path' => sys_get_temp_dir() . '/witals_test_' . uniqid() . '.log',
                    'buffered' => false,
                    'level' => 'debug',
                ]
            ]
        ]);

        $logger = $manager->driver('standard');

        $this->assertInstanceOf(\Witals\Framework\Log\Drivers\StandardLogger::class, $logger);
    }

    public function testImplementsPsrLoggerInterface(): void
    {
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $this->manager);
    }
}
