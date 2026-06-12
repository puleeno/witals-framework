<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Auth\TokenStorage;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Auth\TokenStorage\ArrayTokenStorage;
use Witals\Framework\Auth\Token;
use DateTime;

class ArrayTokenStorageTest extends TestCase
{
    private ArrayTokenStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new ArrayTokenStorage();
    }

    public function testCreateReturnsToken(): void
    {
        $payload = ['user_id' => 1];
        $token = $this->storage->create($payload);

        $this->assertInstanceOf(Token::class, $token);
        $this->assertSame($payload, $token->getPayload());
    }

    public function testCreateGeneratesUniqueId(): void
    {
        $token1 = $this->storage->create(['user_id' => 1]);
        $token2 = $this->storage->create(['user_id' => 2]);

        $this->assertNotSame($token1->getID(), $token2->getID());
    }

    public function testCreateWithExpiration(): void
    {
        $expiresAt = new DateTime('+1 hour');
        $payload = ['user_id' => 1];
        $token = $this->storage->create($payload, $expiresAt);

        $this->assertSame($expiresAt, $token->getExpiresAt());
    }

    public function testLoadReturnsToken(): void
    {
        $payload = ['user_id' => 1];
        $token = $this->storage->create($payload);

        $loaded = $this->storage->load($token->getID());

        $this->assertSame($token->getID(), $loaded->getID());
        $this->assertSame($payload, $loaded->getPayload());
    }

    public function testLoadReturnsNullForNonExistentToken(): void
    {
        $loaded = $this->storage->load('non_existent_id');

        $this->assertNull($loaded);
    }

    public function testDeleteRemovesToken(): void
    {
        $token = $this->storage->create(['user_id' => 1]);

        $this->storage->delete($token);

        $loaded = $this->storage->load($token->getID());

        $this->assertNull($loaded);
    }

    public function testDeleteNonExistentTokenDoesNothing(): void
    {
        $token = new Token('non_existent', ['user_id' => 1]);

        $this->storage->delete($token);

        $this->expectNotToPerformAssertions();
    }

    public function testMultipleTokensStoredIndependently(): void
    {
        $token1 = $this->storage->create(['user_id' => 1]);
        $token2 = $this->storage->create(['user_id' => 2]);

        $loaded1 = $this->storage->load($token1->getID());
        $loaded2 = $this->storage->load($token2->getID());

        $this->assertSame(['user_id' => 1], $loaded1->getPayload());
        $this->assertSame(['user_id' => 2], $loaded2->getPayload());
    }

    public function testStorageIsNonPersistent(): void
    {
        $token = $this->storage->create(['user_id' => 1]);
        $tokenId = $token->getID();

        $newStorage = new ArrayTokenStorage();
        $loaded = $newStorage->load($tokenId);

        $this->assertNull($loaded);
    }
}
