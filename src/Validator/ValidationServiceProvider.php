<?php

declare(strict_types=1);

namespace Witals\Framework\Validator;

use Witals\Framework\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ValidatorInterface::class, function () {
            return new Validator();
        });

        $this->app->alias(ValidatorInterface::class, 'validator');
    }
}
