<?php

declare(strict_types=1);

namespace Witals\Framework\Console;

use Witals\Framework\Support\ServiceProvider;
use Witals\Framework\Console\Commands\MakeModuleCommand;
use Witals\Framework\Console\Commands\MakeBlockCommand;
use Witals\Framework\Console\Commands\MakeCommandCommand;
use Witals\Framework\Console\Commands\MakeProviderCommand;

class ConsoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Kernel::class, function ($app) {
            $kernel = new Kernel($app);

            $kernel->register(ServeCommand::class);
            $kernel->register(SchemaSyncCommand::class);
            $kernel->register(DbSeedCommand::class);
            $kernel->register(ConfigCacheCommand::class);
            $kernel->register(ConfigClearCommand::class);
            $kernel->register(DownCommand::class);
            $kernel->register(UpCommand::class);

            // Generator Commands
            $kernel->register(MakeModuleCommand::class);
            $kernel->register(MakeBlockCommand::class);
            $kernel->register(MakeCommandCommand::class);
            $kernel->register(MakeProviderCommand::class);

            return $kernel;
        });
    }
}
