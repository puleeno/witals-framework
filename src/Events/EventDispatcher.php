<?php

declare(strict_types=1);

namespace Witals\Framework\Events;

use Witals\Framework\Events\Contracts\EventDispatcherInterface;

class EventDispatcher implements EventDispatcherInterface
{
    protected array $listeners = [];

    protected bool $dispatching = false;

    public function listen(string|array $events, callable|array $listener): void
    {
        foreach ((array) $events as $event) {
            $this->listeners[$event][] = $listener;
        }
    }

    public function dispatch(object $event): void
    {
        if ($this->dispatching) {
            return;
        }

        $this->dispatching = true;

        $eventClass = get_class($event);

        foreach ($this->getListeners($eventClass) as $listener) {
            if (is_array($listener) && count($listener) === 2) {
                [$class, $method] = $listener;
                $instance = new $class();
                $instance->$method($event);
            } else {
                $listener($event);
            }
        }

        $this->dispatching = false;
    }

    public function removeListener(string $event, callable $listener): void
    {
        $this->listeners[$event] = array_filter(
            $this->listeners[$event] ?? [],
            fn ($l) => $l !== $listener,
        );
    }

    public function hasListeners(string $event): bool
    {
        return isset($this->listeners[$event]) && $this->listeners[$event] !== [];
    }

    public function getListeners(string $event): array
    {
        return $this->listeners[$event] ?? [];
    }
}
