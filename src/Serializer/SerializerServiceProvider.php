<?php

declare(strict_types=1);

namespace Witals\Framework\Serializer;

use Witals\Framework\Support\ServiceProvider;

class SerializerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(JsonSerializer::class, function () {
            return new JsonSerializer();
        });

        $this->app->singleton(ArraySerializer::class, function () {
            return new ArraySerializer();
        });

        $this->app->singleton(SerializerInterface::class, function ($app) {
            return new Serializer(
                $app->make(JsonSerializer::class),
                $app->make(ArraySerializer::class)
            );
        });

        $this->app->alias(SerializerInterface::class, 'serializer');
    }
}
