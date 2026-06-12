<?php

declare(strict_types=1);

namespace Witals\Framework\Module;

use Witals\Framework\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleManager::class, function ($app) {
            return new ModuleManager($app);
        });

        $this->app->singleton(Hook::class, function () {
            return new Hook();
        });

        $this->app->alias(ModuleManager::class, 'module.manager');
        $this->app->alias(Hook::class, 'hooks');
    }

    public function boot(): void
    {
        // Snapshot providers before module loading
        // (modules register their providers during loadModule()->register(),
        //  but $this->app->booted is still false during bootProviders(),
        //  so we must explicitly boot them after loading.)
        $previousProviders = $this->app->getLoadedProvidersKeys();

        // Load all support modules at boot (no routes, just services)
        $manager = $this->app->make(ModuleManager::class);
        $manager->loadSupportModules();

        // Boot any service providers that were registered by modules
        $this->app->bootNewProviders($previousProviders);

        // Register console commands
        $kernel = $this->app->make(\Witals\Framework\Console\Kernel::class);

        $kernel->register(\Witals\Framework\Module\Console\ModuleListCommand::class);
        $kernel->register(\Witals\Framework\Module\Console\ModuleDiscoverCommand::class);
        $kernel->register(\Witals\Framework\Module\Console\ModuleValidateCommand::class);
    }
}
