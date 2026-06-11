<?php

declare(strict_types=1);

namespace Witals\Framework\Events\Contracts;

interface EventDispatcherInterface
{
    public function listen(string|array $events, callable|array $listener): void;

    public function dispatch(object $event): void;

    public function removeListener(string $event, callable $listener): void;

    public function hasListeners(string $event): bool;

    public function getListeners(string $event): array;
}
