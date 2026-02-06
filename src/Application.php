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
use Witals\Framework\Contracts\Exceptions\ExceptionHandlerInterface;
use Witals\Framework\Exceptions\Handler as ExceptionHandler;
use Witals\Framework\Bootstrap\HandleExceptions;
use Throwable;

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
    protected array $terminatingCallbacks = [];
    protected array $bootingCallbacks = [];
    protected array $bootedCallbacks = [];
    protected array $beforeRequestCallbacks = [];
    protected array $bootstrappers = [];
    protected array $hasBootstrapped = [];

    public function __construct(string $basePath, ?RuntimeType $runtime = null)
    {
        $this->basePath = $basePath;
        $this->runtime = $runtime ?? RuntimeType::detect();

        $this->registerBaseBindings();
        $this->registerCoreContainerAliases();
        $this->bootstrapWith([
            HandleExceptions::class,
        ]);
    }

    /**
     * Register the basic bindings into the container.
     */
    protected function registerBaseBindings(): void
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(self::class, $this);
        $this->instance(\Witals\Framework\Contracts\Container::class, $this);

        // Initialize core services
        $this->initializeView();
        
        $this->singleton(ExceptionHandlerInterface::class, function ($app) {
            return new ExceptionHandler($app);
        });
        
        $this->alias(ExceptionHandlerInterface::class, ExceptionHandler::class);
    }

    /**
     * Run the given array of bootstrap classes.
     */
    public function bootstrapWith(array $bootstrappers): void
    {
        foreach ($bootstrappers as $bootstrapper) {
            if (isset($this->hasBootstrapped[$bootstrapper])) {
                continue;
            }

            $this->make($bootstrapper)->bootstrap($this);
            $this->hasBootstrapped[$bootstrapper] = true;
        }
    }

    /**
     * Add a bootstrapper to the application.
     */
    public function addBootstrapper(string $bootstrapper): void
    {
        $this->bootstrappers[] = $bootstrapper;
    }

    /**
     * Run all registered bootstrappers.
     */
    public function bootstrap(): void
    {
        $this->bootstrapWith($this->bootstrappers);
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
     * Register a custom exception handler.
     */
    public function withExceptions(string|ExceptionHandlerInterface $handler): void
    {
        $this->singleton(ExceptionHandlerInterface::class, $handler);
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

        foreach ($this->bootingCallbacks as $callback) {
            $this->call($callback);
        }

        $this->lifecycle()->onBoot();
        
        $this->booted = true;

        foreach ($this->bootedCallbacks as $callback) {
            $this->call($callback);
        }
    }

    /**
     * Register a booting callback.
     */
    public function booting(callable $callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a booted callback.
     */
    public function booted(callable $callback): void
    {
        $this->bootedCallbacks[] = $callback;
    }

    /**
     * Register a before request callback.
     */
    public function beforeRequest(callable $callback): void
    {
        $this->beforeRequestCallbacks[] = $callback;
    }

    /**
     * Call before request callbacks.
     */
    public function callBeforeRequestCallbacks(Request $request): void
    {
        foreach ($this->beforeRequestCallbacks as $callback) {
            $this->call($callback, ['request' => $request]);
        }
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
     * Get the destination for PHP error logging.
     * Framework uses this early in the bootstrap process.
     */
    public function getErrorLogPath(): string
    {
        return $this->basePath('storage/logs/witals.log');
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

        // 2. Lifecycle onRequestStart
        $this->lifecycle()->onRequestStart($request);

        // 3. Trigger beforeRequest callbacks
        $this->callBeforeRequestCallbacks($request);

        // 4. Create the RequestHandler (which manages init, execute, respond, shutdown)
        $handler = new \Witals\Framework\Http\RequestHandler(
            $this,
            $this->make(\Witals\Framework\Contracts\Http\Kernel::class)
        );

        return $this->runScope(
            [Request::class => $request],
            fn () => $handler->handle($request)
        );
    }

    /**
     * Register a terminating callback.
     */
    public function terminating(callable $callback): void
    {
        $this->terminatingCallbacks[] = $callback;
    }

    /**
     * Terminate the application.
     */
    public function terminate(Request $request, Response $response): void
    {
        $this->callTerminatingCallbacks();

        if ($this->isLongRunning()) {
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
            $this->callTerminatingCallbacks();
            
            // Clear request-scoped state in manager
            if ($this->stateManager && method_exists($this->stateManager, 'afterRequest')) {
                $this->stateManager->afterRequest();
            }

            $this->flushLogs();
            gc_collect_cycles();
        }
    }

    protected function callTerminatingCallbacks(): void
    {
        foreach ($this->terminatingCallbacks as $callback) {
            $this->call($callback);
        }
        
        // Clear callbacks for next request if long-running
        if ($this->isLongRunning()) {
            $this->terminatingCallbacks = [];
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
     * Handle an uncaught exception.
     */
    public function handleException(Throwable $e): void
    {
        $handler = $this->make(ExceptionHandlerInterface::class);
        
        $handler->report($e);
        
        $request = null;
        try {
            if ($this->has(Request::class)) {
                $request = $this->make(Request::class);
            }
        } catch (Throwable) {
            // Ignore if request cannot be resolved
        }

        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            $handler->render($e, $request)->send();
        }
    }

    /**
     * Check if running in unit test environment
     */
    public function isRunningUnitTests(): bool
    {
        return defined('PHPUNIT_WITALS_TESTSUITE') || getenv('APP_ENV') === 'testing';
    }
}
