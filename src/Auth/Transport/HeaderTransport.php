<?php

declare(strict_types=1);

namespace Witals\Framework\Auth\Transport;

use Witals\Framework\Contracts\Auth\HttpTransportInterface;
use Witals\Framework\Contracts\Auth\TokenInterface;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use DateTimeInterface;

class HeaderTransport implements HttpTransportInterface
{
    public function __construct(
        protected string $header = 'X-Auth-Token'
    ) {
    }

    public function fetchToken(Request $request): ?string
    {
        $token = $request->header($this->header);
        
        if (is_array($token)) {
            return $token[0] ?? null;
        }
        
        return is_string($token) && !empty($token) ? $token : null;
    }

    public function commitToken(Request $request, Response $response, TokenInterface $token, DateTimeInterface $expiresAt = null): Response
    {
        // Headers are usually set on the response object
        return $response->withHeader($this->header, $token->getID());
    }

    public function removeToken(Request $request, Response $response, TokenInterface $token): Response
    {
        // We cannot really "delete" a header from the client side, but we can set it to empty
        return $response->withHeader($this->header, '');
    }
}
