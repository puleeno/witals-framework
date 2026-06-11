<?php

declare(strict_types=1);

namespace Witals\Framework\Queue;

use Witals\Framework\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QueueManager::class, function ($app) {
            $config = $app->has('config') ? $app->make('config') : [];
            $queueConfig = $config['queue'] ?? $this->getDefaultConfig();

            $manager = new QueueManager($queueConfig);

            if ($app->has(\Psr\Log\LoggerInterface::class)) {
                $manager->setLogger($app->make(\Psr\Log\LoggerInterface::class));
            }

            return $manager;
        });

        $this->app->alias(QueueManager::class, 'queue');
    }

    public function boot(): void
    {
        $kernel = $this->app->make(\Witals\Framework\Console\Kernel::class);

        $kernel->register(\Witals\Framework\Queue\Console\QueueWorkCommand::class);
    }

    protected function getDefaultConfig(): array
    {
        return [
            'default' => 'sync',
            'connections' => [
                'sync' => ['driver' => 'sync'],
                'database' => [
                    'driver' => 'database',
                    'connection' => 'default',
                    'table' => 'jobs',
                    'queue' => 'default',
                    'retry_after' => 90,
                ],
                'redis' => [
                    'driver' => 'redis',
                    'queue' => 'default',
                    'retry_after' => 90,
                    'block_for' => 5,
                ],
            ],
        ];
    }
}
