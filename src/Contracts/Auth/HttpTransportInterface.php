<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts\Auth;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use DateTimeInterface;

interface HttpTransportInterface
{
    /**
     * Fetch token ID from request.
     */
    public function fetchToken(Request $request): ?string;

    /**
     * Commit token to response (e.g. set cookie or header).
     */
    public function commitToken(Request $request, Response $response, TokenInterface $token, DateTimeInterface $expiresAt = null): Response;

    /**
     * Remove token from response (e.g. delete cookie).
     */
    public function removeToken(Request $request, Response $response, TokenInterface $token): Response;
}
