<?php

declare(strict_types=1);

namespace Witals\Framework\Events;

use Witals\Framework\Application;

abstract class Event
{
    public function broadcastOn(): array
    {
        return [];
    }

    public function broadcastAs(): ?string
    {
        return null;
    }
}
