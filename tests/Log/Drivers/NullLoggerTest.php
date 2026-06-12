<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Log\Drivers;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Log\Drivers\NullLogger;

class NullLoggerTest extends TestCase
{
    public function testLogDoesNothing(): void
    {
        $logger = new NullLogger();

        $this->expectNotToPerformAssertions();

        $logger->log('info', 'Test message');
        $logger->error('Error message');
        $logger->debug('Debug message');
    }

    public function testLogWithContextDoesNothing(): void
    {
        $logger = new NullLogger();

        $this->expectNotToPerformAssertions();

        $logger->log('info', 'Test message', ['key' => 'value']);
    }

    public function testLogWithStringableDoesNothing(): void
    {
        $logger = new NullLogger();
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'Stringable message';
            }
        };

        $this->expectNotToPerformAssertions();

        $logger->log('info', $stringable);
    }

    public function testImplementsPsrLoggerInterface(): void
    {
        $logger = new NullLogger();

        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $logger);
    }
}
