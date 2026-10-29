<?php

declare(strict_types=1);

namespace Witals\Framework\Auth\Transport;

use Witals\Framework\Contracts\Auth\HttpTransportInterface;
use Witals\Framework\Contracts\Auth\TokenInterface;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use DateTimeInterface;

class CookieTransport implements HttpTransportInterface
{
    public function __construct(
        protected string $cookieName = 'token',
        protected ?string $domain = null,
        protected string $sameSite = 'Lax',
        protected bool $secure = false,
    ) {
        if ($this->domain === null) {
            $this->domain = env('AUTH_COOKIE_DOMAIN');
        }
        if ($envSameSite = env('AUTH_COOKIE_SAMESITE')) {
            $this->sameSite = $envSameSite;
        }
        if ($envSecure = env('AUTH_COOKIE_SECURE')) {
            $this->secure = filter_var($envSecure, FILTER_VALIDATE_BOOLEAN);
        }
    }

    public function fetchToken(Request $request): ?string
    {
        return $request->cookie($this->cookieName);
    }

    public function commitToken(Request $request, Response $response, TokenInterface $token, ?DateTimeInterface $expiresAt = null): Response
    {
        $value = $token->getID();
        $expires = $expiresAt ? $expiresAt->getTimestamp() : 0;
        $path = '/';
        $domain = $this->resolveDomain($request);
        $secure = $this->secure || $this->isSecure($request);
        $httponly = true;
        $sameSite = $this->sameSite;

        if (strtolower($sameSite) === 'none') {
            $secure = true;
        }

        $cookieParts = [];
        $cookieParts[] = sprintf('%s=%s', $this->cookieName, urlencode($value));
        $cookieParts[] = 'Path=' . $path;
        if ($expires) {
            $cookieParts[] = 'Expires=' . gmdate('D, d M Y H:i:s T', $expires);
        }
        if ($domain !== '' && $domain !== null) {
            $cookieParts[] = 'Domain=' . $domain;
        }
        if ($secure) {
            $cookieParts[] = 'Secure';
        }
        if ($httponly) {
            $cookieParts[] = 'HttpOnly';
        }
        $cookieParts[] = 'SameSite=' . $sameSite;

        $cookieValue = implode('; ', $cookieParts);

        return $response->withHeader('Set-Cookie', $cookieValue);
    }

    public function removeToken(Request $request, Response $response, TokenInterface $token): Response
    {
        $secure = $this->secure || $this->isSecure($request);
        $domain = $this->resolveDomain($request);
        $sameSite = $this->sameSite;

        if (strtolower($sameSite) === 'none') {
            $secure = true;
        }

        $cookieParts = [];
        $cookieParts[] = sprintf('%s=; Expires=Thu, 01 Jan 1970 00:00:00 GMT', $this->cookieName);
        $cookieParts[] = 'Path=/';
        if ($domain !== '' && $domain !== null) {
            $cookieParts[] = 'Domain=' . $domain;
        }
        if ($secure) {
            $cookieParts[] = 'Secure';
        }
        $cookieParts[] = 'HttpOnly';
        $cookieParts[] = 'SameSite=' . $sameSite;

        $cookieValue = implode('; ', $cookieParts);
        return $response->withHeader('Set-Cookie', $cookieValue);
    }

    protected function resolveDomain(Request $request): ?string
    {
        if ($this->domain !== null && $this->domain !== '') {
            return $this->domain;
        }

        $host = (string) ($request->header('Host') ?? $request->server('HTTP_HOST') ?? '');
        if ($host === '') {
            return null;
        }
        if (str_contains($host, ':')) {
            $host = explode(':', $host)[0];
        }
        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }
        return $host;
    }

    protected function isSecure(Request $request): bool
    {
        $forwarded = $request->header('X-Forwarded-Proto');
        if (is_string($forwarded) && strtolower($forwarded) === 'https') {
            return true;
        }

        $https = $request->server('HTTPS');
        return is_string($https) && $https !== '' && strtolower($https) !== 'off';
    }
}
