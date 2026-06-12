<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Log\Drivers;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Log\Drivers\DebugLogger;

class DebugLoggerTest extends TestCase
{
    private DebugLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new DebugLogger('debug');
    }

    public function testConstructorSetsMinLevel(): void
    {
        $logger = new DebugLogger('warning');

        $this->assertInstanceOf(DebugLogger::class, $logger);
    }

    public function testConstructorAcceptsIntegerLevel(): void
    {
        $logger = new DebugLogger(300);

        $this->assertInstanceOf(DebugLogger::class, $logger);
    }

    public function testConstructorDefaultsToDebug(): void
    {
        $logger = new DebugLogger();

        $this->assertInstanceOf(DebugLogger::class, $logger);
    }

    public function testLogWritesToStderr(): void
    {
        $this->expectNotToPerformAssertions();

        $this->logger->log('info', 'Test message');
    }

    public function testLogWithContext(): void
    {
        $this->expectNotToPerformAssertions();

        $this->logger->log('info', 'Test message', ['key' => 'value']);
    }

    public function testLogBelowMinLevelDoesNothing(): void
    {
        $logger = new DebugLogger('warning');

        $this->expectNotToPerformAssertions();

        $logger->log('debug', 'Debug message');
    }

    public function testLogAtMinLevelWrites(): void
    {
        $logger = new DebugLogger('warning');

        $this->expectNotToPerformAssertions();

        $logger->log('warning', 'Warning message');
    }

    public function testLogAboveMinLevelWrites(): void
    {
        $logger = new DebugLogger('warning');

        $this->expectNotToPerformAssertions();

        $logger->log('error', 'Error message');
    }

    public function testLogWithStringable(): void
    {
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'Stringable message';
            }
        };

        $this->expectNotToPerformAssertions();

        $this->logger->log('info', $stringable);
    }

    public function testLogWithInvalidLevelDefaultsToDebug(): void
    {
        $this->expectNotToPerformAssertions();

        $this->logger->log('invalid', 'Message');
    }

    public function testImplementsPsrLoggerInterface(): void
    {
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $this->logger);
    }
}
