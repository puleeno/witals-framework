<?php

declare(strict_types=1);

namespace Witals\Framework\Console;

use Witals\Framework\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Kernel::class, function ($app) {
            $kernel = new Kernel($app);
            
            // Register default commands
            $kernel->register(ServeCommand::class);
            $kernel->register(SchemaSyncCommand::class);
            $kernel->register(DbSeedCommand::class);
            
            return $kernel;
        });
    }
}
