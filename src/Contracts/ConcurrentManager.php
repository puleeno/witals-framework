<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts;

interface ConcurrentManager
{
    public function isEnabled(): bool;

    public function run(callable $fn): mixed;

    public function all(array $tasks): array;
}
