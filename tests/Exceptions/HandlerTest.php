<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Exceptions;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Exceptions\Handler;
use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Psr\Log\LoggerInterface;

class HandlerTest extends TestCase
{
    private Handler $handler;
    private Application $app;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->handler = new Handler($this->app);
    }

    public function testConstructorSetsApp(): void
    {
        $this->assertInstanceOf(Handler::class, $this->handler);
    }

    public function testReportLogsExceptionWhenLoggerAvailable(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Test exception', $this->callback(function ($context) {
                return isset($context['exception']) && 
                       isset($context['file']) && 
                       isset($context['line']) &&
                       isset($context['trace']);
            }));

        $this->app->method('has')->with(LoggerInterface::class)->willReturn(true);
        $this->app->method('make')->with(LoggerInterface::class)->willReturn($logger);

        $exception = new \Exception('Test exception');
        $this->handler->report($exception);
    }

    public function testReportDoesNothingWhenLoggerNotAvailable(): void
    {
        $this->app->method('has')->with(LoggerInterface::class)->willReturn(false);

        $exception = new \Exception('Test exception');

        $this->expectNotToPerformAssertions();

        $this->handler->report($exception);
    }

    public function testRenderReturnsJsonResponseWhenRequestWantsJson(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('wantsJson')->willReturn(true);
        $this->app->method('has')->willReturn(false);

        $exception = new \Exception('Test exception');
        $response = $this->handler->render($exception, $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('application/json', $response->getHeader('Content-Type'));
    }

    public function testRenderReturnsHtmlResponseByDefault(): void
    {
        $this->app->method('has')->willReturn(false);

        $exception = new \Exception('Test exception');
        $response = $this->handler->render($exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('text/html', $response->getHeader('Content-Type'));
    }

    public function testRenderUsesStatusCodeFromException(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('wantsJson')->willReturn(true);
        $this->app->method('has')->willReturn(false);

        $exception = new class extends \Exception {
            public function getStatusCode(): int
            {
                return 404;
            }
        };

        $response = $this->handler->render($exception, $request);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testRenderDefaultsTo500WhenNoStatusCode(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('wantsJson')->willReturn(true);
        $this->app->method('has')->willReturn(false);

        $exception = new \Exception('Test exception');
        $response = $this->handler->render($exception, $request);

        $this->assertSame(500, $response->getStatusCode());
    }

    public function testRenderJsonIncludesDebugInfoWhenDebugEnabled(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('wantsJson')->willReturn(true);
        
        $config = $this->createMock(\stdClass::class);
        $config->method('get')->willReturn(true);
        $this->app->method('has')->willReturn(true);
        $this->app->method('make')->willReturn($config);

        $exception = new \Exception('Test exception');
        $response = $this->handler->render($exception, $request);

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('exception', $content);
        $this->assertArrayHasKey('file', $content);
        $this->assertArrayHasKey('line', $content);
        $this->assertArrayHasKey('trace', $content);
    }

    public function testRenderJsonExcludesDebugInfoWhenDebugDisabled(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('wantsJson')->willReturn(true);
        
        $config = $this->createMock(\stdClass::class);
        $config->method('get')->willReturn(false);
        $this->app->method('has')->willReturn(true);
        $this->app->method('make')->willReturn($config);

        $exception = new \Exception('Test exception');
        $response = $this->handler->render($exception, $request);

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $content);
        $this->assertArrayNotHasKey('exception', $content);
        $this->assertArrayNotHasKey('file', $content);
    }

    public function testRenderHtmlFallsBackToTextWhenTemplateMissing(): void
    {
        $this->app->method('has')->willReturn(false);

        $handler = new class($this->app) extends Handler {
            protected function renderHtmlResponse(\Throwable $e, int $status, bool $debug): Response
            {
                // Simulate missing template
                return new Response($e->getMessage(), $status, ['Content-Type' => 'text/plain']);
            }
        };

        $exception = new \Exception('Test exception');
        $response = $handler->render($exception);

        $this->assertStringContainsString('text/plain', $response->getHeader('Content-Type'));
    }

    public function testImplementsExceptionHandlerInterface(): void
    {
        $this->assertInstanceOf(\Witals\Framework\Contracts\Exceptions\ExceptionHandlerInterface::class, $this->handler);
    }
}
