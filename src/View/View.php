<?php

declare(strict_types=1);

namespace Witals\Framework\View;

use Witals\Framework\Contracts\View\View as ViewContract;
use Witals\Framework\Contracts\View\Engine;

class View implements ViewContract
{
    protected string $view;
    protected string $path;
    protected array $data;
    protected Engine $engine;

    public function __construct(string $view, string $path, array $data, Engine $engine)
    {
        $this->view = $view;
        $this->path = $path;
        $this->data = $data;
        $this->engine = $engine;
    }

    public function render(): string
    {
        return $this->engine->get($this->path, $this->data);
    }

    public function name(): string
    {
        return $this->view;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function with(string|array $key, mixed $value = null): static
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
