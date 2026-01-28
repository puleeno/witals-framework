<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts\View;

/**
 * View Contract
 * Represents a renderable template
 */
interface View
{
    /**
     * Get the string contents of the view.
     *
     * @return string
     */
    public function render(): string;

    /**
     * Get the name of the view.
     *
     * @return string
     */
    public function name(): string;

    /**
     * Get the data bound to the view.
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Add data to the view.
     *
     * @param string|array $key
     * @param mixed $value
     * @return $this
     */
    public function with(string|array $key, mixed $value = null): static;
}
