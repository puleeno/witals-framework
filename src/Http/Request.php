<?php

declare(strict_types=1);

namespace Witals\Framework\Http;

use Psr\Http\Message\ServerRequestInterface;

/**
 * HTTP Request wrapper
 * Supports both traditional PHP globals and PSR-7 requests
 */
class Request
{
    protected string $method;
    protected string $uri;
    protected array $headers;
    protected array $query;
    protected array $post;
    protected array $files;
    protected array $server;
    protected array $cookies;
    protected ?string $body;

    public function __construct(
        string $method,
        string $uri,
        array $headers = [],
        array $query = [],
        array $post = [],
        array $files = [],
        array $server = [],
        array $cookies = [],
        ?string $body = null
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->headers = $headers;
        $this->query = $query;
        $this->post = $post;
        $this->files = $files;
        $this->server = $server;
        $this->cookies = $cookies;
        $this->body = $body;
    }

    /**
     * Create request from PHP globals (traditional web server)
     */
    public static function createFromGlobals(): self
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            }
        }

        return new self(
            method: $_SERVER['REQUEST_METHOD'] ?? 'GET',
            uri: $_SERVER['REQUEST_URI'] ?? '/',
            headers: $headers,
            query: $_GET,
            post: $_POST,
            files: $_FILES,
            server: $_SERVER,
            cookies: $_COOKIE,
            body: file_get_contents('php://input')
        );
    }

    /**
     * Create request from PSR-7 request (RoadRunner)
     */
    public static function createFromPsr7(ServerRequestInterface $psr7Request): self
    {
        $headers = [];
        foreach ($psr7Request->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        return new self(
            method: $psr7Request->getMethod(),
            uri: (string) $psr7Request->getUri(),
            headers: $headers,
            query: $psr7Request->getQueryParams(),
            post: $psr7Request->getParsedBody() ?? [],
            files: $psr7Request->getUploadedFiles(),
            server: $psr7Request->getServerParams(),
            cookies: $psr7Request->getCookieParams(),
            body: (string) $psr7Request->getBody()
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function path(): string
    {
        return parse_url($this->uri, PHP_URL_PATH) ?? '/';
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        $input = array_merge($this->query, $this->post);
        if ($key === null) {
            return $input;
        }
        return $input[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        return $this->headers[$key] ?? $default;
    }

    public function body(): ?string
    {
        return $this->body;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function isJson(): bool
    {
        return str_contains($this->header('Content-Type', ''), 'application/json');
    }
}
