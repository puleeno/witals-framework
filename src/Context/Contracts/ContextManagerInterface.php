<?php

declare(strict_types=1);

namespace Witals\Framework\Context\Contracts;

use Witals\Framework\Http\Request;

interface ContextManagerInterface
{
    public function registerContext(string $type, string $identifier, string $module, ContextInterface $context): void;

    public function unregisterContext(string $type, string $identifier): void;

    public function getContext(string $type, string $identifier): ?ContextInterface;

    public function getContextsByType(string $type): array;

    public function getContextsByModule(string $module): array;

    public function getAllContexts(): array;

    public function getContextTypes(): array;

    public function resolveContext(Request $request): ?ContextInterface;

    public function hasContext(string $type, string $identifier): bool;
}
