<?php

declare(strict_types=1);

namespace Witals\Framework\Database\Crud;

use Witals\Framework\Support\ServiceProvider;

class CrudServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // CRUD logic is mostly trait-based and controller-based,
        // so we don't need heavy singleton registration here yet.
    }

    public function boot(): void
    {
        //
    }
}
