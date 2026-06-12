<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Auth\Transport;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Auth\Transport\CookieTransport;
use Witals\Framework\Auth\Token;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use DateTime;

class CookieTransportTest extends TestCase
{
    private CookieTransport $transport;

    protected function setUp(): void
    {
        $this->transport = new CookieTransport('auth_token');
    }

    public function testFetchTokenReturnsCookieValue(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('cookie')->with('auth_token')->willReturn('token123');

        $token = $this->transport->fetchToken($request);

        $this->assertSame('token123', $token);
    }

    public function testFetchTokenReturnsNullWhenCookieNotSet(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('cookie')->with('auth_token')->willReturn(null);

        $token = $this->transport->fetchToken($request);

        $this->assertNull($token);
    }

    public function testCommitTokenSetsCookieHeader(): void
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $token = new Token('token123', ['user_id' => 1]);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Set-Cookie', $this->stringContains('auth_token=token123'))
            ->willReturn($response);

        $this->transport->commitToken($request, $response, $token);
    }

    public function testCommitTokenWithExpiration(): void
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $expiresAt = new DateTime('+1 hour');
        $token = new Token('token123', ['user_id' => 1], $expiresAt);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Set-Cookie', $this->stringContains('Expires='))
            ->willReturn($response);

        $this->transport->commitToken($request, $response, $token, $expiresAt);
    }

    public function testCommitTokenSetsSecureAndHttpOnly(): void
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $token = new Token('token123', ['user_id' => 1]);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Set-Cookie', $this->callback(function ($value) {
                return strpos($value, 'Secure') !== false && strpos($value, 'HttpOnly') !== false;
            }))
            ->willReturn($response);

        $this->transport->commitToken($request, $response, $token);
    }

    public function testCommitTokenSetsSameSiteLax(): void
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $token = new Token('token123', ['user_id' => 1]);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Set-Cookie', $this->stringContains('SameSite=Lax'))
            ->willReturn($response);

        $this->transport->commitToken($request, $response, $token);
    }

    public function testRemoveTokenSetsExpiredCookie(): void
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $token = new Token('token123', ['user_id' => 1]);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Set-Cookie', $this->stringContains('Expires=Thu, 01 Jan 1970'))
            ->willReturn($response);

        $this->transport->removeToken($request, $response, $token);
    }

    public function testCustomCookieName(): void
    {
        $transport = new CookieTransport('custom_token');
        $request = $this->createMock(Request::class);
        $request->method('cookie')->with('custom_token')->willReturn('token123');

        $token = $transport->fetchToken($request);

        $this->assertSame('token123', $token);
    }

    public function testCommitTokenUrlEncodesValue(): void
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $token = new Token('token with spaces', ['user_id' => 1]);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Set-Cookie', $this->callback(function ($value) {
                return strpos($value, 'auth_token=token+with+spaces') !== false;
            }))
            ->willReturn($response);

        $this->transport->commitToken($request, $response, $token);
    }
}
