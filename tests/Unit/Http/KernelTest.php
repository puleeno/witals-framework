<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use App\Http\Kernel;
use App\Services\PageService;
use Psr\Log\LoggerInterface;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use App\Exceptions\TemplateNotFoundException;
use App\Exceptions\RenderException;

class KernelTest extends TestCase
{
    private function createKernel(PageService $pageService): Kernel
    {
        $logger = $this->createMock(LoggerInterface::class);
        return new Kernel($pageService, $logger);
    }

    public function test_handle_returns_200_on_success(): void
    {
        $pageService = $this->createMock(PageService::class);
        $pageService->method('handle')->willReturn('<html><body>OK</body></html>');

        $kernel = $this->createKernel($pageService);
        $request = new Request('GET', '/', [], [], [], [], [], [], null);

        $response = $kernel->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_handle_returns_html_content_type(): void
    {
        $pageService = $this->createMock(PageService::class);
        $pageService->method('handle')->willReturn('<html><body>OK</body></html>');

        $kernel = $this->createKernel($pageService);
        $request = new Request('GET', '/', [], [], [], [], [], [], null);

        $response = $kernel->handle($request);

        $this->assertStringContainsString('text/html', $response->getHeader('Content-Type'));
    }

    public function test_handle_passes_request_to_page_service(): void
    {
        $pageService = $this->createMock(PageService::class);
        $pageService->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(Request::class))
            ->willReturn('<html></html>');

        $kernel = $this->createKernel($pageService);
        $request = new Request('GET', '/', [], [], [], [], [], [], null);

        $kernel->handle($request);
    }

    public function test_handle_returns_404_on_template_not_found(): void
    {
        $pageService = $this->createMock(PageService::class);
        $pageService->method('handle')->willThrowException(new TemplateNotFoundException());

        $kernel = $this->createKernel($pageService);
        $request = new Request('GET', '/missing', [], [], [], [], [], [], null);

        $response = $kernel->handle($request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('Page not found', $response->getContent());
    }

    public function test_handle_returns_500_on_render_error(): void
    {
        $pageService = $this->createMock(PageService::class);
        $pageService->method('handle')->willThrowException(new RenderException());

        $kernel = $this->createKernel($pageService);
        $request = new Request('GET', '/', [], [], [], [], [], [], null);

        $response = $kernel->handle($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('Internal server error', $response->getContent());
    }

    public function test_handle_returns_200_after_mocked_page_service(): void
    {
        $pageService = $this->createMock(PageService::class);
        $pageService->method('handle')->willReturn('<html><body>OK</body></html>');

        $kernel = $this->createKernel($pageService);
        $request = new Request('GET', '/', [], [], [], [], [], [], null);

        $response = $kernel->handle($request);

        $this->assertSame(200, $response->getStatusCode());
    }
}
