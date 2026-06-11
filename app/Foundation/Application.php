<?php

declare(strict_types=1);

namespace App\Foundation;

use Witals\Framework\Application as BaseApplication;
use App\Foundation\Module\ModuleManager;

/**
 * Application
 * 
 * Extends Witals Framework Application with Service Provider support
 */
class Application extends BaseApplication
{
    /**
     * Module Manager
     */
    protected ?ModuleManager $moduleManager = null;

    /**
     * Registered service providers
     */
    protected array $serviceProviders = [];

    /**
     * Loaded service providers
     */
    protected array $loadedProviders = [];

    /**
     * Booted service providers
     */
    protected array $bootedProviders = [];
    protected array $config = [];

    /**
     * Register a service provider
     */
    public function register(object|string $provider): mixed
    {
        // If string, instantiate
        if (is_string($provider)) {
            try {
                $provider = new $provider($this);
            } catch (\Throwable $e) {
                error_log(sprintf(
                    '[App] Failed to instantiate provider %s: %s',
                    is_string($provider) ? $provider : get_class($provider),
                    $e->getMessage()
                ));
                return null;
            }
        }

        $providerClass = get_class($provider);

        // Skip if already registered
        if (isset($this->serviceProviders[$providerClass])) {
            return $this->serviceProviders[$providerClass];
        }

        // Register the provider
        if (method_exists($provider, 'register')) {
            try {
                $provider->register();
            } catch (\Throwable $e) {
                error_log(sprintf(
                    '[App] Failed to register provider %s: %s',
                    $providerClass,
                    $e->getMessage()
                ));
                return null;
            }
        }
        
        $this->serviceProviders[$providerClass] = $provider;
        $this->loadedProviders[$providerClass] = true;

        if ($this->booted && method_exists($provider, 'boot')) {
            try {
                $provider->boot();
            } catch (\Throwable $e) {
                error_log(sprintf(
                    '[App] Failed to boot provider %s: %s',
                    $providerClass,
                    $e->getMessage()
                ));
            }
        }

        return $provider;
    }

    /**
     * Register multiple providers
     */
    public function registerProviders(array $providers): void
    {
        foreach ($providers as $provider) {
            $this->register($provider);
        }
    }

    /**
     * Boot all registered providers
     */
    protected function bootProviders(): void
    {
        foreach ($this->serviceProviders as $class => $provider) {
            if (!isset($this->bootedProviders[$class])) {
                try {
                    $provider->boot();
                } catch (\Throwable $e) {
                    error_log(sprintf(
                        '[App] Failed to boot provider %s: %s',
                        $class,
                        $e->getMessage()
                    ));
                }
                $this->bootedProviders[$class] = true;
            }
        }
    }

    /**
     * Data to be bound before boot
     */
    public function registerCoreContainerAliases(): void
    {
        parent::registerCoreContainerAliases();

        // Initialize Module Manager
        $this->moduleManager = new ModuleManager($this);
        $this->instance(ModuleManager::class, $this->moduleManager);
    }

    public function registerConfiguredProviders(): void
    {
        parent::registerConfiguredProviders();

        $this->singleton(
            \Witals\Framework\Contracts\Http\Kernel::class,
            \App\Http\Kernel::class
        );

        $this->registerProviders([
            \Witals\Framework\Auth\AuthServiceProvider::class,
            \App\Providers\LogServiceProvider::class,
            \App\Providers\DatabaseServiceProvider::class,
            \App\Providers\ViewServiceProvider::class,
            \App\Providers\RouteServiceProvider::class,
            \App\Providers\ConsoleServiceProvider::class,
            \App\Providers\AppServiceProvider::class,
        ]);
    }

    /**
     * Boot the application (override parent)
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Run all registered bootstrappers (including those added by packages)
        $this->bootstrap();

        // Discover and load modules (they register their own providers)
        if ($this->moduleManager) {
            $this->moduleManager->discover();
            $this->moduleManager->loadEnabled();
        }

        // Boot providers
        $this->bootProviders();

        // Then boot parent (lifecycle)
        parent::boot();
    }

    /**
     * Get config value with dot notation
     */
    public function config(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $file = array_shift($keys);
        
        $configPath = $this->basePath("config/{$file}.php");
        
        if (!file_exists($configPath)) {
            return $default;
        }

        if (isset($this->config[$file])) {
            $config = $this->config[$file];
        } else {
            $config = require $configPath;
            $this->config[$file] = $config;
        }
        
        foreach ($keys as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }
            $config = $config[$segment];
        }
        
        return $config;
    }

    /**
     * Get all registered providers
     */
    public function getProviders(): array
    {
        return $this->serviceProviders;
    }

    /**
     * Check if provider is registered
     */
    public function hasProvider(string $provider): bool
    {
        return isset($this->serviceProviders[$provider]);
    }

    /**
     * Customize the error log destination.
     */
    public function getErrorLogPath(): string
    {
        return $this->basePath('storage/logs/app.log');
    }

    /**
     * "Extend" an abstract type in the container.
     */
    public function extend(string $abstract, \Closure $closure): void
    {
        parent::extend($abstract, $closure);
    }
}
