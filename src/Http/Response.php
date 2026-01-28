<?php

declare(strict_types=1);

namespace Witals\Framework\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP Response wrapper
 * Supports both traditional PHP output and PSR-7 responses
 */
class Response
{
    protected string $content;
    protected int $statusCode;
    protected array $headers;

    public function __construct(
        string $content = '',
        int $statusCode = 200,
        array $headers = []
    ) {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Create a JSON response
     */
    public static function json(array $data, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';
        return new self(
            json_encode($data, JSON_THROW_ON_ERROR),
            $statusCode,
            $headers
        );
    }

    /**
     * Create an HTML response
     */
    public static function html(string $html, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';
        return new self($html, $statusCode, $headers);
    }

    /**
     * Send response (traditional web server)
     */
    public function send(): void
    {
        // Set status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Send content
        echo $this->content;
    }

    /**
     * Convert to PSR-7 response (RoadRunner)
     */
    public function toPsr7(ResponseFactoryInterface $factory): ResponseInterface
    {
        $response = $factory->createResponse($this->statusCode);

        // Add headers
        foreach ($this->headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        // Add content
        $response->getBody()->write($this->content);

        return $response;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name, mixed $default = null): mixed
    {
        return $this->headers[$name] ?? $default;
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    public function withStatus(int $statusCode): self
    {
        $clone = clone $this;
        $clone->statusCode = $statusCode;
        return $clone;
    }
}
