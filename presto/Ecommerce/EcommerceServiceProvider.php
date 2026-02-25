<?php

declare(strict_types=1);

namespace PrestoWorld\Ecommerce;

use App\Support\ServiceProvider;

class EcommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('ecommerce.registry', function () {
            return new class {
                private array $types = [];
                public function register(string $type, string $handlerClass, array $metadata = []): void {
                    $this->types[$type] = ['handler' => $handlerClass, 'metadata' => $metadata];
                }
                public function getTypes(): array { return $this->types; }
            };
        });

        $this->app->singleton(OrderManager::class, function ($app) {
            return new OrderManager($app->make(\Cycle\Database\DatabaseProviderInterface::class));
        });

        error_log("Ecommerce: PrestoWorld Core Service Provider registered.");
    }
}
