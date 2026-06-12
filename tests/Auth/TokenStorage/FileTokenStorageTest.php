<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Auth\TokenStorage;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Auth\TokenStorage\FileTokenStorage;
use DateTime;

class FileTokenStorageTest extends TestCase
{
    private FileTokenStorage $storage;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/witals_token_test_' . uniqid();
        $this->storage = new FileTokenStorage($this->tempDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function testConstructorCreatesDirectory(): void
    {
        $this->assertDirectoryExists($this->tempDir);
    }

    public function testCreateReturnsToken(): void
    {
        $payload = ['user_id' => 1];
        $token = $this->storage->create($payload);

        $this->assertSame($payload, $token->getPayload());
        $this->assertNotEmpty($token->getID());
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

    public function testLoadReturnsNullForExpiredToken(): void
    {
        $expiresAt = new DateTime('-1 hour');
        $payload = ['user_id' => 1];
        $token = $this->storage->create($payload, $expiresAt);

        $loaded = $this->storage->load($token->getID());

        $this->assertNull($loaded);
    }

    public function testLoadDeletesExpiredTokenFile(): void
    {
        $expiresAt = new DateTime('-1 hour');
        $payload = ['user_id' => 1];
        $token = $this->storage->create($payload, $expiresAt);

        $this->storage->load($token->getID());

        $filePath = $this->tempDir . '/' . $token->getID() . '.json';
        $this->assertFileDoesNotExist($filePath);
    }

    public function testDeleteRemovesTokenFile(): void
    {
        $token = $this->storage->create(['user_id' => 1]);
        $filePath = $this->tempDir . '/' . $token->getID() . '.json';

        $this->assertFileExists($filePath);

        $this->storage->delete($token);

        $this->assertFileDoesNotExist($filePath);
    }

    public function testDeleteNonExistentTokenDoesNothing(): void
    {
        $token = new class('non_existent', ['user_id' => 1]) {
            public function __construct(private string $id, private array $payload) {}
            public function getID(): string { return $this->id; }
            public function getPayload(): array { return $this->payload; }
            public function getExpiresAt(): ?\DateTimeInterface { return null; }
        };

        $this->storage->delete($token);

        $this->expectNotToPerformAssertions();
    }

    public function testStorageIsPersistent(): void
    {
        $token = $this->storage->create(['user_id' => 1]);
        $tokenId = $token->getID();

        $newStorage = new FileTokenStorage($this->tempDir);
        $loaded = $newStorage->load($tokenId);

        $this->assertSame($tokenId, $loaded->getID());
        $this->assertSame(['user_id' => 1], $loaded->getPayload());
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

    public function testLoadHandlesCorruptedFile(): void
    {
        $tokenId = 'corrupted_token';
        $filePath = $this->tempDir . '/' . $tokenId . '.json';
        file_put_contents($filePath, 'invalid json content');

        $loaded = $this->storage->load($tokenId);

        $this->assertNull($loaded);
    }
}
