<?php

declare(strict_types=1);

namespace Witals\Framework\Http;

use Witals\Framework\Contracts\Http\ControllerInterface;

/**
 * Base Controller providing common functionality
 */
abstract class AbstractController implements ControllerInterface
{
    /**
     * Create a JSON response
     */
    protected function json(array $data, int $statusCode = 200, array $headers = []): Response
    {
        return Response::json($data, $statusCode, $headers);
    }

    /**
     * Create an HTML response
     */
    protected function html(string $html, int $statusCode = 200, array $headers = []): Response
    {
        return Response::html($html, $statusCode, $headers);
    }

    /**
     * Create a redirect response
     */
    protected function redirect(string $url, int $statusCode = 302, array $headers = []): Response
    {
        return Response::redirect($url, $statusCode, $headers);
    }
}
