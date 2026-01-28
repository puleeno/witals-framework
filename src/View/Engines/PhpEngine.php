<?php

declare(strict_types=1);

namespace Witals\Framework\View\Engines;

use Witals\Framework\Contracts\View\Engine;

class PhpEngine implements Engine
{
    /**
     * Get the evaluated contents of the view at the given path.
     *
     * @param string $path
     * @param array $data
     * @return string
     * @throws \Throwable
     */
    public function get(string $path, array $data = []): string
    {
        return $this->evaluatePath($path, $data);
    }

    /**
     * Evaluate a template at the given path with the given data.
     *
     * @param string $__path
     * @param array $__data
     * @return string
     * @throws \Throwable
     */
    protected function evaluatePath(string $__path, array $__data): string
    {
        $obLevel = ob_get_level();

        ob_start();

        extract($__data, EXTR_SKIP);

        try {
            include $__path;
        } catch (\Throwable $e) {
            $this->handleViewException($e, $obLevel);
        }

        return ltrim(ob_get_clean());
    }

    /**
     * Handle an exception that occurred during view rendering.
     *
     * @param \Throwable $e
     * @param int $obLevel
     * @return void
     * @throws \Throwable
     */
    protected function handleViewException(\Throwable $e, int $obLevel): void
    {
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw $e;
    }
}
