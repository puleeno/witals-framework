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
        // Load all support modules at boot (no routes, just services)
        $manager = $this->app->make(ModuleManager::class);
        $manager->loadSupportModules();

        // Register console commands
        $kernel = $this->app->make(\Witals\Framework\Console\Kernel::class);

        $kernel->register(\Witals\Framework\Module\Console\ModuleListCommand::class);
        $kernel->register(\Witals\Framework\Module\Console\ModuleDiscoverCommand::class);
        $kernel->register(\Witals\Framework\Module\Console\ModuleValidateCommand::class);
    }
}
