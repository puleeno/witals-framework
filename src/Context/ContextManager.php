<?php

declare(strict_types=1);

namespace Witals\Framework\Context;

use Witals\Framework\Context\Contracts\ContextInterface;
use Witals\Framework\Context\Contracts\ContextManagerInterface;
use Witals\Framework\Http\Request;

class ContextManager implements ContextManagerInterface
{
    protected array $contexts = [];
    protected array $moduleIndex = [];
    protected array $typeIndex = [];

    public function registerContext(string $type, string $identifier, string $module, ContextInterface $context): void
    {
        $key = $this->key($type, $identifier);

        $this->contexts[$key] = $context;
        $this->moduleIndex[$module][$key] = $context;
        $this->typeIndex[$type][$key] = $context;
    }

    public function unregisterContext(string $type, string $identifier): void
    {
        $key = $this->key($type, $identifier);

        if (!isset($this->contexts[$key])) {
            return;
        }

        $context = $this->contexts[$key];

        foreach ($this->moduleIndex as $module => &$contexts) {
            unset($contexts[$key]);
        }

        unset($this->typeIndex[$type][$key]);
        unset($this->contexts[$key]);
    }

    public function getContext(string $type, string $identifier): ?ContextInterface
    {
        return $this->contexts[$this->key($type, $identifier)] ?? null;
    }

    public function getContextsByType(string $type): array
    {
        return array_values($this->typeIndex[$type] ?? []);
    }

    public function getContextsByModule(string $module): array
    {
        return array_values($this->moduleIndex[$module] ?? []);
    }

    public function getAllContexts(): array
    {
        return array_values($this->contexts);
    }

    public function getContextTypes(): array
    {
        return array_keys($this->typeIndex);
    }

    public function resolveContext(Request $request): ?ContextInterface
    {
        foreach ($this->contexts as $context) {
            if ($context->matches($request)) {
                $resolved = clone $context;
                $resolved->setData($context->getData());
                return $resolved;
            }
        }

        return null;
    }

    public function hasContext(string $type, string $identifier): bool
    {
        return isset($this->contexts[$this->key($type, $identifier)]);
    }

    protected function key(string $type, string $identifier): string
    {
        return $type . '::' . $identifier;
    }
}
