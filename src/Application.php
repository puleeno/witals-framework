<?php

declare(strict_types=1);

namespace Witals\Framework;

use Witals\Framework\Container\Container;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Contracts\StateManager;
use Witals\Framework\State\StateManagerFactory;
use Witals\Framework\Contracts\LifecycleManager;
use Witals\Framework\Lifecycle\LifecycleFactory;

/**
 * Main Application Class
 * Handles request lifecycle and environment detection
 */
class Application extends Container
{
    protected string $basePath;
    protected bool $isRoadRunner = false;
    protected array $providers = [];
    protected ?StateManager $stateManager = null;
    protected ?LifecycleManager $lifecycle = null;
    protected bool $booted = false;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;

        $this->registerBaseBindings();
        $this->registerCoreContainerAliases();
    }

    /**
     * Register the basic bindings into the container.
     */
    protected function registerBaseBindings(): void
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(self::class, $this);
    }

    /**
     * Register the core class aliases in the container.
     */
    protected function registerCoreContainerAliases(): void
    {
        $this->alias('app', self::class);
    }

    /**
     * Alias a type to a different name.
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->bind($alias, function ($app) use ($abstract) {
            return $app->make($abstract);
        });
    }

    /**
     * Initialize state manager based on environment
     */
    protected function initializeStateManager(): void
    {
        if ($this->stateManager === null) {
            $this->stateManager = StateManagerFactory::create($this);

            // Bind to container
            $this->instance(StateManager::class, $this->stateManager);
        }
    }

    /**
     * Get state manager instance
     */
    public function state(): StateManager
    {
        if ($this->stateManager === null) {
            $this->initializeStateManager();
        }

        return $this->stateManager;
    }

    /**
     * Set RoadRunner mode
     */
    public function setRoadRunnerMode(bool $enabled): void
    {
        $this->isRoadRunner = $enabled;

        // Initialize managers after setting mode
        $this->initializeStateManager();
        $this->initializeLifecycle();
    }

    /**
     * Initialize lifecycle manager based on environment
     */
    protected function initializeLifecycle(): void
    {
        if ($this->lifecycle === null) {
            $this->lifecycle = LifecycleFactory::create($this);

            // Bind to container
            $this->instance(LifecycleManager::class, $this->lifecycle);
        }
    }

    /**
     * Get lifecycle manager instance
     */
    public function lifecycle(): LifecycleManager
    {
        if ($this->lifecycle === null) {
            $this->initializeLifecycle();
        }

        return $this->lifecycle;
    }

    /**
     * Boot the application
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->lifecycle()->onBoot();
        $this->booted = true;
    }

    /**
     * Check if running on RoadRunner
     */
    public function isRoadRunner(): bool
    {
        return $this->isRoadRunner;
    }

    /**
     * Get base path
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Register configured service providers
     */
    public function registerConfiguredProviders(): void
    {
        // Register your service providers here
        // Example: $this->register(new RouteServiceProvider($this));
    }

    /**
     * Handle an incoming HTTP request
     */
    public function handle(Request $request): Response
    {
        // Use runScope to ensure request isolation
        // Any service resolved during this closure (that wasn't already resolved)
        // will be automatically cleaned up when the closure ends.
        return $this->runScope(
            [Request::class => $request],
            function () use ($request) {
                try {
                    // Ensure application is booted
                    $this->boot();

                    // Lifecycle: Request Start
                    $this->lifecycle()->onRequestStart($request);

                    // Handle the request
                    $kernel = $this->make(\Witals\Framework\Contracts\Http\Kernel::class);
                    $response = $kernel->handle($request);

                    // Lifecycle: Request End
                    $this->lifecycle()->onRequestEnd($request, $response);

                    return $response;
                } catch (\Throwable $e) {
                    $response = $this->handleException($e);

                    // Still call request end even on error
                    if (isset($request)) {
                        $this->lifecycle()->onRequestEnd($request, $response);
                    }

                    return $response;
                }
            }
        );
    }

    /**
     * Perform any final actions for the request lifecycle
     */
    public function terminate(Request $request, Response $response): void
    {
        // Lifecycle: Terminate (traditional mode only)
        if (!$this->isRoadRunner) {
            $this->lifecycle()->onTerminate();
        }
    }

    /**
     * Clean up after request (RoadRunner specific)
     */
    public function afterRequest(Request $request, Response $response): void
    {
        if ($this->isRoadRunner) {
            // Note: Instance cleanup is handled by runScope() in handle()

            // Clear request-scoped state in manager
            if ($this->stateManager && method_exists($this->stateManager, 'afterRequest')) {
                $this->stateManager->afterRequest();
            }

            // Run garbage collection
            gc_collect_cycles();
        }
    }

    /**
     * Handle uncaught exceptions
     */
    protected function handleException(\Throwable $e): Response
    {
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

        return new Response(
            json_encode([
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]),
            $statusCode,
            ['Content-Type' => 'application/json']
        );
    }
}
