<?php

declare(strict_types=1);

namespace Witals\Framework\Context;

interface LayoutStorage
{
    public function get(string $type, string $identifier): ?array;

    public function set(string $type, string $identifier, array $blockTree): void;

    public function delete(string $type, string $identifier): void;

    public function has(string $type, string $identifier): bool;
}
