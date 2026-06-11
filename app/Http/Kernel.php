<?php

declare(strict_types=1);

namespace App\Http;

use Witals\Framework\Application;
use Witals\Framework\Contracts\Http\Kernel as KernelContract;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Psr\Log\LoggerInterface;

/**
 * HTTP Kernel
 * Handles HTTP request processing and middleware
 */
class Kernel implements KernelContract
{


    protected Application $app;
    protected LoggerInterface $logger;

    public function __construct(Application $app, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->logger = $logger;
    }

    protected array $middleware = [
        \App\Http\Middleware\CorsMiddleware::class,
        \App\Http\Middleware\LocaleMiddleware::class,
        \Witals\Framework\Auth\Middleware\AuthMiddleware::class,
        \App\Http\Middleware\AdminAuthMiddleware::class,
    ];

    /**
     * Handle an incoming HTTP request
     */
    public function handle(Request $request): Response
    {
        // Reset debug bar for the current request
        if ($this->app->has(\App\Foundation\Debug\DebugBar::class)) {
            $this->app->make(\App\Foundation\Debug\DebugBar::class)->reset();
        }

        // Bind the current request instance to the container
        $this->app->instance(Request::class, $request);

        $this->logger->info("Incoming request: {method} {uri}", [
            'method' => $request->method(),
            'uri' => $request->uri(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);

        // Create Middleware Pipeline
        $pipeline = $this->middleware;
        
        // Add Router Dispatch as the final destination
        $pipeline[] = function ($request) {
            return $this->dispatchToRouter($request);
        };

        return $this->sendRequestThroughPipeline($request, $pipeline);
    }

    /**
     * Execute the middleware pipeline
     */
    protected function sendRequestThroughPipeline(Request $request, array $pipeline): Response
    {
        $middleware = array_shift($pipeline);

        if ($middleware === null) {
             // Should not happen if pipeline always has the destination
             throw new \RuntimeException("Middleware pipeline exhausted without response");
        }

        // Create the callback for the NEXT middleware in line
        $next = function ($nextRequest) use ($pipeline) {
            return $this->sendRequestThroughPipeline($nextRequest, $pipeline);
        };

        // If generic closure
        if ($middleware instanceof \Closure) {
            return $middleware($request, $next);
        }

        // If class string
        if (is_string($middleware)) {
             $instance = $this->app->make($middleware);
             if (method_exists($instance, 'handle')) {
                 return $instance->handle($request, $next);
             }
        }

        throw new \RuntimeException("Invalid middleware: " . json_encode($middleware));
    }

    /**
     * Dispatch request to Router
     */
    protected function dispatchToRouter(Request $request): Response
    {
        try {
            $router = $this->app->make(\App\Http\Routing\Router::class);
            $result = $router->dispatch($request);

            if ($result instanceof Response) {
                return $this->injectDebugBar($request, $result);
            }

            return Response::html((string)$result);

        } catch (\Throwable $e) {
            $this->logger->error("Request error: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            error_log("[Kernel Error] " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return Response::json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Inject Debug Bar into HTML responses
     */
    protected function injectDebugBar(Request $request, Response $response): Response
    {
        // Skip for redirects or non-HTML responses
        if ($response->getStatusCode() >= 300 && $response->getStatusCode() < 400) {
            return $response;
        }

        if (!env('APP_DEBUG_BAR', false) || !$this->app->has(\App\Foundation\Debug\DebugBar::class)) {
            return $response;
        }

        $content = $response->getContent();
        if (!is_string($content) || !str_contains($response->getHeader('Content-Type', ''), 'text/html')) {
            return $response;
        }

        $debugBar = $this->app->make(\App\Foundation\Debug\DebugBar::class);
        $debugBarHtml = $debugBar->render();
        
        if (str_contains($content, '</body>')) {
            $content = str_replace('</body>', $debugBarHtml . '</body>', $content);
        } else {
            $content .= $debugBarHtml;
        }

        return new Response($content, $response->getStatusCode(), $response->getHeaders());
    }

    /**
     * Handle home route
     */
    public function handleHome(Request $request): Response
    {
        $modules = [];
        if (app()->has(\App\Foundation\Module\ModuleManager::class)) {
            $manager = app(\App\Foundation\Module\ModuleManager::class);
            foreach ($manager->all() as $module) {
                $modules[] = [
                    'name' => $module->getName(),
                    'version' => $module->getVersion(),
                    'priority' => $module->getPriority(),
                    'enabled' => $module->isEnabled() ? 'Yes' : 'No',
                    'loaded' => $manager->isLoaded($module->getName()) ? 'Yes' : 'No',
                    'path' => $module->getPath(),
                    'type' => $module->getType(),
                ];
            }
        }

        return Response::json([
            'message' => 'Welcome!',
            'runtime' => $this->getEnvironmentName(),
            'modules' => $modules,
        ]);
    }

    /**
     * Handle health check route
     */
    public function handleHealth(Request $request): Response
    {
        return Response::json([
            'status' => 'healthy',
            'environment' => $this->getEnvironmentName(),
            'timestamp' => time(),
            'uptime' => $this->getUptime(),
        ]);
    }

    /**
     * Handle info route
     */
    public function handleInfo(Request $request): Response
    {
        return Response::json([
            'app' => [
                'name' => 'Witals Framework',
                'environment' => $this->getEnvironmentName(),
                'is_roadrunner' => $this->app->isRoadRunner(),
                'database' => $this->checkDatabase(),
                'db_user' => env('DB_USERNAME', 'unknown'),
                'db_prefix' => env('WP_TABLE_PREFIX', 'unknown'),
                'auth_key_sample' => substr(env('WP_AUTH_KEY', 'none'), 0, 10) . '...',
            ],
            'php' => [
                'version' => PHP_VERSION,
                'sapi' => PHP_SAPI,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
            'server' => [
                'software' => $this->getServerInfo(),
                'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
            ],
            'performance' => [
                'memory_usage' => $this->getMemoryUsage(),
                'peak_memory' => $this->getPeakMemory(),
                'uptime' => $this->getUptime(),
            ],
        ]);
    }

    protected function checkDatabase(): string
    {
        try {
            if (!$this->app->has(\Cycle\Database\DatabaseProviderInterface::class)) {
                return 'Not Configured';
            }
            $dbal = $this->app->make(\Cycle\Database\DatabaseProviderInterface::class);
            $db = $dbal->database();
            $driver = $db->getDriver();
            $driver->connect(); // Ensure connection is established
            return 'Connected (' . get_class($driver) . ')';
        } catch (\Throwable $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    protected function getEnvironmentName(): string
    {
        if ($this->app->isRoadRunner()) return 'RoadRunner';
        if ($this->app->isReactPhp()) return 'ReactPHP';
        if ($this->app->isSwoole()) return 'Swoole';
        if ($this->app->isOpenSwoole()) return 'OpenSwoole';
        
        return 'Traditional Web Server';
    }

    protected function getPhpVersion(): string
    {
        return PHP_VERSION;
    }

    protected function getServerInfo(): string
    {
        if ($this->app->isRoadRunner()) return 'RoadRunner';
        if ($this->app->isReactPhp()) return 'ReactPHP (Event Loop)';
        if ($this->app->isSwoole()) return 'Swoole Server';
        if ($this->app->isOpenSwoole()) return 'OpenSwoole Server';

        return $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown SAPI';
    }

    protected function getMemoryUsage(): string
    {
        return $this->formatBytes(memory_get_usage(true));
    }

    protected function getPeakMemory(): string
    {
        return $this->formatBytes(memory_get_peak_usage(true));
    }

    protected function getUptime(): string
    {
        if (!defined('WITALS_START')) {
            return 'N/A';
        }
        $uptime = microtime(true) - WITALS_START;
        return number_format($uptime, 3) . 's';
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
