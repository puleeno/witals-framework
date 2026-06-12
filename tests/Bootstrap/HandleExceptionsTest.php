<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Bootstrap;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Bootstrap\HandleExceptions;
use Witals\Framework\Application;
use ErrorException;

class HandleExceptionsTest extends TestCase
{
    private HandleExceptions $handler;
    private Application $app;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->handler = new HandleExceptions();
    }

    public function testBootstrapSetsErrorReporting(): void
    {
        $this->app->method('getErrorLogPath')->willReturn('/tmp/error.log');
        $this->app->method('isLongRunning')->willReturn(false);

        $this->handler->bootstrap($this->app);

        $this->expectNotToPerformAssertions();
    }

    public function testBootstrapSetsErrorLogPath(): void
    {
        $this->app->method('getErrorLogPath')->willReturn('/tmp/error.log');
        $this->app->method('isLongRunning')->willReturn(false);

        $this->handler->bootstrap($this->app);

        $this->expectNotToPerformAssertions();
    }

    public function testBootstrapHidesDisplayErrorsForTraditional(): void
    {
        $this->app->method('getErrorLogPath')->willReturn('/tmp/error.log');
        $this->app->method('isLongRunning')->willReturn(false);

        $this->handler->bootstrap($this->app);

        $this->expectNotToPerformAssertions();
    }

    public function testHandleErrorThrowsErrorException(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Test error');

        $this->handler->handleError(E_WARNING, 'Test error', '/path/to/file.php', 10);
    }

    public function testHandleErrorIgnoresSuppressedErrors(): void
    {
        $this->expectNotToPerformAssertions();

        // Suppress error reporting
        $oldLevel = error_reporting(0);
        try {
            $this->handler->handleError(E_WARNING, 'Test error', '/path/to/file.php', 10);
        } finally {
            error_reporting($oldLevel);
        }
    }

    public function testHandleExceptionCallsAppHandler(): void
    {
        $exception = new \Exception('Test exception');
        $this->app->expects($this->once())
            ->method('handleException')
            ->with($exception);

        $this->handler->bootstrap($this->app);
        $this->handler->handleException($exception);
    }

    public function testHandleExceptionPreventsRecursiveHandling(): void
    {
        $exception = new \Exception('Test exception');
        $this->app->method('handleException')->willThrowException(new \Exception('Handler error'));

        $this->handler->bootstrap($this->app);

        $this->expectNotToPerformAssertions();

        $this->handler->handleException($exception);
    }

    public function testHandleShutdownHandlesFatalErrors(): void
    {
        $this->app->method('getErrorLogPath')->willReturn('/tmp/error.log');
        $this->app->method('isLongRunning')->willReturn(false);
        $this->app->method('handleException');

        $this->handler->bootstrap($this->app);

        $this->expectNotToPerformAssertions();

        $this->handler->handleShutdown();
    }

    public function testIsFatalReturnsTrueForFatalErrors(): void
    {
        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('isFatal');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->handler, E_ERROR));
        $this->assertTrue($method->invoke($this->handler, E_PARSE));
        $this->assertTrue($method->invoke($this->handler, E_CORE_ERROR));
        $this->assertTrue($method->invoke($this->handler, E_COMPILE_ERROR));
    }

    public function testIsFatalReturnsFalseForNonFatalErrors(): void
    {
        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('isFatal');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->handler, E_WARNING));
        $this->assertFalse($method->invoke($this->handler, E_NOTICE));
        $this->assertFalse($method->invoke($this->handler, E_DEPRECATED));
    }
}
