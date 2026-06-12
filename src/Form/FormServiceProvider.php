<?php

declare(strict_types=1);

namespace Witals\Framework\Form;

use Witals\Framework\Support\ServiceProvider;

class FormServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FormBuilder::class, function () {
            return new FormBuilder();
        });

        $this->app->alias(FormBuilder::class, 'form');
    }
}
