<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Auth;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Auth\Token;
use DateTime;

class TokenTest extends TestCase
{
    public function testConstructorSetsIdAndPayload(): void
    {
        $token = new Token('token123', ['user_id' => 1, 'role' => 'admin']);

        $this->assertSame('token123', $token->getID());
        $this->assertSame(['user_id' => 1, 'role' => 'admin'], $token->getPayload());
    }

    public function testConstructorWithExpiration(): void
    {
        $expiresAt = new DateTime('+1 hour');
        $token = new Token('token123', ['user_id' => 1], $expiresAt);

        $this->assertSame($expiresAt, $token->getExpiresAt());
    }

    public function testConstructorWithoutExpiration(): void
    {
        $token = new Token('token123', ['user_id' => 1]);

        $this->assertNull($token->getExpiresAt());
    }

    public function testGetIdReturnsCorrectValue(): void
    {
        $token = new Token('abc-123-xyz', ['data' => 'test']);

        $this->assertSame('abc-123-xyz', $token->getID());
    }

    public function testGetPayloadReturnsCorrectValue(): void
    {
        $payload = ['user_id' => 42, 'permissions' => ['read', 'write']];
        $token = new Token('token123', $payload);

        $this->assertSame($payload, $token->getPayload());
    }

    public function testGetPayloadReturnsArrayCopy(): void
    {
        $payload = ['user_id' => 1];
        $token = new Token('token123', $payload);

        $returnedPayload = $token->getPayload();
        $returnedPayload['new_key'] = 'new_value';

        $this->assertArrayNotHasKey('new_key', $token->getPayload());
    }

    public function testGetExpiresAtReturnsDateTimeInterface(): void
    {
        $expiresAt = new DateTime('+1 day');
        $token = new Token('token123', ['user_id' => 1], $expiresAt);

        $this->assertInstanceOf(\DateTimeInterface::class, $token->getExpiresAt());
    }

    public function testTokenWithEmptyPayload(): void
    {
        $token = new Token('token123', []);

        $this->assertSame([], $token->getPayload());
    }

    public function testTokenWithComplexPayload(): void
    {
        $payload = [
            'user_id' => 1,
            'roles' => ['admin', 'editor'],
            'metadata' => [
                'ip' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0',
            ],
        ];
        $token = new Token('token123', $payload);

        $this->assertSame($payload, $token->getPayload());
    }
}
