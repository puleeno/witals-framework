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

    public function commitToken(Request $request, Response $response, TokenInterface $token, DateTimeInterface $expiresAt = null): Response
    {
        // Simple Set-Cookie header construction
        $value = $token->getID();
        $expires = $expiresAt ? $expiresAt->getTimestamp() : 0;
        $path = '/';
        $domain = ''; // default
        $secure = true;
        $httponly = true;
        
        $cookieValue = sprintf(
            '%s=%s; Path=%s; %s%s%s',
            $this->cookieName,
            urlencode($value),
            $path,
            $expires ? 'Expires=' . gmdate('D, d M Y H:i:s T', $expires) . '; ' : '',
            $secure ? 'Secure; ' : '',
            $httponly ? 'HttpOnly; ' : ''
        );

        return $response->withHeader('Set-Cookie', $cookieValue);
    }

    public function removeToken(Request $request, Response $response, TokenInterface $token): Response
    {
         $cookieValue = sprintf(
            '%s=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Secure; HttpOnly',
            $this->cookieName
        );
        return $response->withHeader('Set-Cookie', $cookieValue);
    }
}
