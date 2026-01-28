<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts\View;

/**
 * View Engine Contract
 * Defines how to render specific template types
 */
interface Engine
{
    /**
     * Get the evaluated contents of the view at the given path.
     *
     * @param string $path
     * @param array $data
     * @return string
     */
    public function get(string $path, array $data = []): string;
}
