<?php

declare(strict_types=1);

namespace Witals\Framework\Context;

use Witals\Framework\Context\Contracts\ContextInterface;
use Witals\Framework\Http\Request;

class ContextInstance implements ContextInterface
{
    protected array $data = [];
    protected array $blockTree = [];

    public function __construct(
        protected string $type,
        protected string $identifier,
        protected string $label = '',
        protected string $description = '',
        protected array $metadata = [],
        protected ?string $template = null,
        protected array $hierarchy = [],
        protected ?string $themeTemplateDir = null,
        protected ?\Closure $matcher = null,
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getBlockTree(): array
    {
        return $this->blockTree;
    }

    public function setBlockTree(array $blockTree): void
    {
        $this->blockTree = $blockTree;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getHierarchy(): array
    {
        return $this->hierarchy;
    }

    public function getThemeTemplateDir(): ?string
    {
        return $this->themeTemplateDir;
    }

    public function matches(Request $request): bool
    {
        if ($this->matcher !== null) {
            return ($this->matcher)($request, $this);
        }

        return false;
    }
}
