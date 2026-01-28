<?php

declare(strict_types=1);

namespace Witals\Framework;

use Witals\Framework\Container\Container;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Contracts\StateManager;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\State\StateManagerFactory;
use Witals\Framework\Contracts\LifecycleManager;
use Witals\Framework\Lifecycle\LifecycleFactory;
use Witals\Framework\Contracts\View\Factory as ViewFactory;
use Witals\Framework\View\ViewManager;

/**
 * Main Application Class
 * Handles request lifecycle and environment detection
 */
class Application extends Container
{
    protected string $basePath;
    protected RuntimeType $runtime;
    protected array $providers = [];
    protected ?StateManager $stateManager = null;
    protected ?LifecycleManager $lifecycle = null;
    protected ?ViewFactory $view = null;
    protected bool $booted = false;

    public function __construct(string $basePath, ?RuntimeType $runtime = null)
    {
        $this->basePath = $basePath;
        $this->runtime = $runtime ?? RuntimeType::detect();

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

        // Initialize core services
        $this->initializeView();
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
     * Set runtime mode
     */
    public function setRuntime(RuntimeType $runtime): void
    {
        $this->runtime = $runtime;

        // Initialize managers after setting mode
        $this->initializeStateManager();
        $this->initializeLifecycle();
    }

    /**
     * Get current runtime type
     */
    public function getRuntime(): RuntimeType
    {
        return $this->runtime;
    }

    /**
     * Set RoadRunner mode
     */
    public function setRoadRunnerMode(bool $active): void
    {
        $this->setRuntime($active ? RuntimeType::ROADRUNNER : RuntimeType::TRADITIONAL);
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
     * Initialize view manager
     */
    protected function initializeView(): void
    {
        if ($this->view === null) {
            $this->view = new ViewManager([$this->basePath('resources/views')]);

            // Bind to container
            $this->instance(ViewFactory::class, $this->view);
            $this->instance('view', $this->view);
        }
    }

    /**
     * Get view factory instance
     */
    public function view(): ViewFactory
    {
        if ($this->view === null) {
            $this->initializeView();
        }

        return $this->view;
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
        return $this->runtime === RuntimeType::ROADRUNNER;
    }

    /**
     * Check if running on ReactPHP
     */
    public function isReactPhp(): bool
    {
        return $this->runtime === RuntimeType::REACTPHP;
    }

    /**
     * Check if running on Swoole
     */
    public function isSwoole(): bool
    {
        return $this->runtime === RuntimeType::SWOOLE;
    }

    /**
     * Check if running on OpenSwoole
     */
    public function isOpenSwoole(): bool
    {
        return $this->runtime === RuntimeType::OPENSWOOLE;
    }

    /**
     * Check if running in traditional mode
     */
    public function isTraditional(): bool
    {
        return $this->runtime === RuntimeType::TRADITIONAL;
    }

    /**
     * Check if running in long-running mode
     */
    public function isLongRunning(): bool
    {
        return $this->runtime->isLongRunning();
    }

    /**
     * Check if runtime supports async operations
     */
    public function isAsync(): bool
    {
        return $this->runtime->isAsync();
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
        // 1. Ensure application is booted
        $this->boot();

        // 2. Create the RequestHandler (which manages init, execute, respond, shutdown)
        $handler = new \Witals\Framework\Http\RequestHandler(
            $this,
            $this->make(\Witals\Framework\Contracts\Http\Kernel::class)
        );

        // 3. Use runScope to ensure request isolation
        // Any service resolved during this closure (that wasn't already resolved)
        // will be automatically cleaned up when the closure ends.
        return $this->runScope(
            [Request::class => $request],
            fn () => $handler->handle($request)
        );
    }

    public function terminate(Request $request, Response $response): void
    {
        // Lifecycle: Terminate (traditional mode only)
        if (!$this->isLongRunning()) {
            $this->lifecycle()->onTerminate();
        }

        $this->flushLogs();
    }

    /**
     * Clean up after request (long-running runtimes)
     */
    public function afterRequest(Request $request, Response $response): void
    {
        if ($this->isLongRunning()) {
            // Note: Instance cleanup is handled by runScope() in handle()

            // Clear request-scoped state in manager
            if ($this->stateManager && method_exists($this->stateManager, 'afterRequest')) {
                $this->stateManager->afterRequest();
            }

            $this->flushLogs();

            // Run garbage collection
            gc_collect_cycles();
        }
    }

    /**
     * Flush logs if the logger supports it.
     */
    protected function flushLogs(): void
    {
        if ($this->has(\Psr\Log\LoggerInterface::class)) {
            $logger = $this->make(\Psr\Log\LoggerInterface::class);
            if (method_exists($logger, 'flush')) {
                $logger->flush();
            }
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
                'file' => $this->isRunningUnitTests() ? $e->getFile() : null,
                'line' => $this->isRunningUnitTests() ? $e->getLine() : null,
            ]),
            $statusCode,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Check if running in unit test environment
     */
    public function isRunningUnitTests(): bool
    {
        return defined('PHPUNIT_WITALS_TESTSUITE') || getenv('APP_ENV') === 'testing';
    }
}
