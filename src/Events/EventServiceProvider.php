<?php

declare(strict_types=1);

namespace Witals\Framework\Events;

use Witals\Framework\Events\Contracts\EventDispatcherInterface;
use Witals\Framework\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected array $listen = [];

    public function register(): void
    {
        $this->app->singleton(EventDispatcherInterface::class, function ($app) {
            $dispatcher = new EventDispatcher();

            foreach ($this->listen as $event => $listeners) {
                foreach ($listeners as $listener) {
                    $dispatcher->listen($event, $listener);
                }
            }

            return $dispatcher;
        });

        $this->app->alias(EventDispatcherInterface::class, 'events');
    }
}
