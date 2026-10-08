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
        protected string $cookieName = 'token'
    ) {
    }

    public function fetchToken(Request $request): ?string
    {
        return $request->cookie($this->cookieName);
    }

    public function commitToken(Request $request, Response $response, TokenInterface $token, ?DateTimeInterface $expiresAt = null): Response
    {
        // Simple Set-Cookie header construction
        $value = $token->getID();
        $expires = $expiresAt ? $expiresAt->getTimestamp() : 0;
        $path = '/';
        $domain = ''; // default
        $secure = $this->isSecure($request);
        $httponly = true;
        $sameSite = 'Lax';
        
        $cookieValue = sprintf(
            '%s=%s; Path=%s; %s%s%sSameSite=%s',
            $this->cookieName,
            urlencode($value),
            $path,
            $expires ? 'Expires=' . gmdate('D, d M Y H:i:s T', $expires) . '; ' : '',
            $secure ? 'Secure; ' : '',
            $httponly ? 'HttpOnly; ' : '',
            $sameSite
        );

        return $response->withHeader('Set-Cookie', $cookieValue);
    }

    public function removeToken(Request $request, Response $response, TokenInterface $token): Response
    {
        $secure = $this->isSecure($request);

         $cookieValue = sprintf(
            '%s=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT; %sHttpOnly; SameSite=Lax',
            $this->cookieName,
            $secure ? 'Secure; ' : '',
        );
        return $response->withHeader('Set-Cookie', $cookieValue);
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
