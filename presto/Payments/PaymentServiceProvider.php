<?php

declare(strict_types=1);

namespace PrestoWorld\Payments;

use App\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentManager::class, function ($app) {
            return new PaymentManager($app->config('payments', []));
        });

        $this->app->alias(PaymentManager::class, 'payment');

        error_log("Payments: PrestoWorld Core Service Provider registered.");
    }
}
