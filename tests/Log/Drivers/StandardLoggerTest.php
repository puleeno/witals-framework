<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Log\Drivers;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Log\Drivers\StandardLogger;
use Witals\Framework\Log\Formatters\LineFormatter;

class StandardLoggerTest extends TestCase
{
    private string $tempFile;
    private StandardLogger $logger;

    protected function setUp(): void
    {
        $this->tempFile = sys_get_temp_dir() . '/witals_log_test_' . uniqid() . '.log';
        $this->logger = new StandardLogger($this->tempFile, false);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testConstructorSetsPath(): void
    {
        $logger = new StandardLogger($this->tempFile);

        $this->assertInstanceOf(StandardLogger::class, $logger);
    }

    public function testConstructorSetsBuffered(): void
    {
        $logger = new StandardLogger($this->tempFile, true);

        $this->assertInstanceOf(StandardLogger::class, $logger);
    }

    public function testConstructorSetsFormatter(): void
    {
        $formatter = new LineFormatter();
        $logger = new StandardLogger($this->tempFile, false, $formatter);

        $this->assertInstanceOf(StandardLogger::class, $logger);
    }

    public function testConstructorSetsMinLevel(): void
    {
        $logger = new StandardLogger($this->tempFile, false, null, 'warning');

        $this->assertInstanceOf(StandardLogger::class, $logger);
    }

    public function testLogWritesToFile(): void
    {
        $this->logger->log('info', 'Test message');

        $this->assertFileExists($this->tempFile);
        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('Test message', $content);
    }

    public function testLogWithBuffer(): void
    {
        $logger = new StandardLogger($this->tempFile, true);
        $logger->log('info', 'Buffered message');

        $this->assertFileDoesNotExist($this->tempFile);

        $logger->flush();

        $this->assertFileExists($this->tempFile);
        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('Buffered message', $content);
    }

    public function testLogBelowMinLevelDoesNothing(): void
    {
        $logger = new StandardLogger($this->tempFile, false, null, 'warning');
        $logger->log('debug', 'Debug message');

        $this->assertFileDoesNotExist($this->tempFile);
    }

    public function testLogAtMinLevelWrites(): void
    {
        $logger = new StandardLogger($this->tempFile, false, null, 'warning');
        $logger->log('warning', 'Warning message');

        $this->assertFileExists($this->tempFile);
    }

    public function testLogWithContext(): void
    {
        $this->logger->log('info', 'Test message', ['key' => 'value']);

        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('Test message', $content);
    }

    public function testPushProcessorAddsProcessor(): void
    {
        $processor = function ($record) {
            $record['message'] = 'Processed: ' . $record['message'];
            return $record;
        };

        $this->logger->pushProcessor($processor);
        $this->logger->log('info', 'Test message');

        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('Processed:', $content);
    }

    public function testPushProcessorReturnsSelf(): void
    {
        $processor = function ($record) {
            return $record;
        };

        $result = $this->logger->pushProcessor($processor);

        $this->assertSame($this->logger, $result);
    }

    public function testFlushWritesBuffer(): void
    {
        $logger = new StandardLogger($this->tempFile, true);
        $logger->log('info', 'Message 1');
        $logger->log('info', 'Message 2');

        $logger->flush();

        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('Message 1', $content);
        $this->assertStringContainsString('Message 2', $content);
    }

    public function testFlushWithEmptyBufferDoesNothing(): void
    {
        $logger = new StandardLogger($this->tempFile, true);

        $logger->flush();

        $this->assertFileDoesNotExist($this->tempFile);
    }

    public function testDestructFlushesBuffer(): void
    {
        $logger = new StandardLogger($this->tempFile, true);
        $logger->log('info', 'Auto flush message');

        unset($logger);

        $this->assertFileExists($this->tempFile);
        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('Auto flush message', $content);
    }

    public function testLogWithStringable(): void
    {
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'Stringable message';
            }
        };

        $this->logger->log('info', $stringable);

        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('Stringable message', $content);
    }

    public function testLogCreatesDirectory(): void
    {
        $path = sys_get_temp_dir() . '/witals_test_dir_' . uniqid() . '/subdir/test.log';
        $logger = new StandardLogger($path, false);

        $logger->log('info', 'Test message');

        $this->assertFileExists($path);
        unlink($path);
        rmdir(dirname($path));
        rmdir(dirname(dirname($path)));
    }

    public function testImplementsPsrLoggerInterface(): void
    {
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $this->logger);
    }
}
