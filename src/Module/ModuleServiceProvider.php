<?php

declare(strict_types=1);

namespace Witals\Framework\Module;

use Witals\Framework\Module\Contracts\HookInterface;
use Witals\Framework\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleDiscoveryService::class, function ($app) {
            return new ModuleDiscoveryService($app);
        });

        $this->app->singleton(ModuleRouter::class, function ($app) {
            return new ModuleRouter($app, $app->make(ModuleDiscoveryService::class));
        });

        $this->app->singleton(ModuleLifecycleManager::class, function ($app) {
            return new ModuleLifecycleManager(
                $app,
                $app->make(ModuleDiscoveryService::class),
                $app->make(\Psr\Log\LoggerInterface::class),
            );
        });

        $this->app->singleton(ModuleManager::class, function ($app) {
            $discovery = $app->make(ModuleDiscoveryService::class);
            $router = $app->make(ModuleRouter::class);
            $lifecycle = $app->make(ModuleLifecycleManager::class);
            $manager = new ModuleManager(
                $app,
                $discovery,
                $router,
                $lifecycle,
                $app->make(\Psr\Log\LoggerInterface::class),
            );
            $router->setModuleLoader(fn(string $name) => $manager->load($name));
            return $manager;
        });

        $this->app->singleton(Hook::class, function () {
            return new Hook();
        });

        $this->app->singleton(HookInterface::class, Hook::class);

        $this->app->alias(ModuleManager::class, 'module.manager');
        $this->app->alias(Hook::class, 'hooks');
    }

    public function boot(): void
    {
        // ─────────────────────────────────────────────────────────────
        // Module Lifecycle — boot order matters here:
        //
        //   1. Snapshot currently-loaded provider keys BEFORE loading
        //      modules. This captures only framework-registered providers.
        //
        //   2. Load all support modules via ModuleManager. Each module's
        //      register() method runs during this call, which may register
        //      new service providers into $this->app->providers[].
        //
        //   3. Call bootNewProviders() to boot ONLY the providers that
        //      were registered by modules (step 2). We pass the snapshot
        //      from step 1 as the "already booted" baseline so those
        //      providers are skipped.
        //
        // Why not let the normal provider boot cycle handle this?
        //   - Provider booting normally happens *before* module loading
        //     via bootProviders(). By the time modules load, the app's
        //     $booted flag is set and bootProviders() won't run again.
        //   - So we manually call bootNewProviders() after module loading
        //     to ensure module-registered providers get their boot() called.
        //
        // If you add a new lifecycle phase (e.g., deferred providers,
        // eager-loaded modules), update this method accordingly.
        // ─────────────────────────────────────────────────────────────

        $previousProviders = $this->app->getLoadedProvidersKeys();

        $manager = $this->app->make(ModuleManager::class);
        $manager->loadSupportModules();

        $this->app->bootNewProviders($previousProviders);

        $kernel = $this->app->make(\Witals\Framework\Console\Kernel::class);

        $kernel->register(\Witals\Framework\Module\Console\ModuleListCommand::class);
        $kernel->register(\Witals\Framework\Module\Console\ModuleDiscoverCommand::class);
        $kernel->register(\Witals\Framework\Module\Console\ModuleValidateCommand::class);
    }
}
