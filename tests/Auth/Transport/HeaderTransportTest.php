<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Auth\Transport;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Auth\Transport\HeaderTransport;
use Witals\Framework\Auth\Token;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use DateTime;

class HeaderTransportTest extends TestCase
{
    private HeaderTransport $transport;

    protected function setUp(): void
    {
        $this->transport = new HeaderTransport('X-Auth-Token');
    }

    public function testFetchTokenReturnsHeaderValue(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('header')->with('X-Auth-Token')->willReturn('token123');

        $token = $this->transport->fetchToken($request);

        $this->assertSame('token123', $token);
    }

    public function testFetchTokenReturnsNullWhenHeaderNotSet(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('header')->with('X-Auth-Token')->willReturn(null);

        $token = $this->transport->fetchToken($request);

        $this->assertNull($token);
    }

    public function testFetchTokenReturnsNullWhenHeaderIsEmpty(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('header')->with('X-Auth-Token')->willReturn('');

        $token = $this->transport->fetchToken($request);

        $this->assertNull($token);
    }

    public function testFetchTokenHandlesArrayHeader(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('header')->with('X-Auth-Token')->willReturn(['token123', 'token456']);

        $token = $this->transport->fetchToken($request);

        $this->assertSame('token123', $token);
    }

    public function testFetchTokenHandlesEmptyArrayHeader(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('header')->with('X-Auth-Token')->willReturn([]);

        $token = $this->transport->fetchToken($request);

        $this->assertNull($token);
    }

    public function testCommitTokenSetsHeader(): void
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $token = new Token('token123', ['user_id' => 1]);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('X-Auth-Token', 'token123')
            ->willReturn($response);

        $this->transport->commitToken($request, $response, $token);
    }

    public function testCommitTokenIgnoresExpiration(): void
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $expiresAt = new DateTime('+1 hour');
        $token = new Token('token123', ['user_id' => 1], $expiresAt);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('X-Auth-Token', 'token123')
            ->willReturn($response);

        $this->transport->commitToken($request, $response, $token, $expiresAt);
    }

    public function testRemoveTokenSetsEmptyHeader(): void
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $token = new Token('token123', ['user_id' => 1]);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('X-Auth-Token', '')
            ->willReturn($response);

        $this->transport->removeToken($request, $response, $token);
    }

    public function testCustomHeaderName(): void
    {
        $transport = new HeaderTransport('X-Custom-Token');
        $request = $this->createMock(Request::class);
        $request->method('header')->with('X-Custom-Token')->willReturn('token123');

        $token = $transport->fetchToken($request);

        $this->assertSame('token123', $token);
    }
}
