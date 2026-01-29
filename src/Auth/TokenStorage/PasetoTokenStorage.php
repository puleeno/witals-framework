<?php

declare(strict_types=1);

namespace Witals\Framework\Auth\TokenStorage;

use Witals\Framework\Contracts\Auth\TokenInterface;
use Witals\Framework\Contracts\Auth\TokenStorageInterface;
use DateTimeInterface;
use ParagonIE\Paseto\Keys\SymmetricKey;
use ParagonIE\Paseto\Protocol\Version4;
use ParagonIE\Paseto\Builder;
use ParagonIE\Paseto\Parser;
use ParagonIE\Paseto\Exception\PasetoException;

/**
 * Paseto Token Storage (Stateless)
 * Uses PASETO v4.local (Symmetric encryption)
 */
class PasetoTokenStorage implements TokenStorageInterface
{
    protected SymmetricKey $key;

    public function __construct(
        string $secretKey // 32-byte hex string or base64
    ) {
        // Ensure key is 32 bytes
        // If it's a hex string, decode it
        if (ctype_xdigit($secretKey)) {
            $rawKey = hex2bin($secretKey);
        } else {
            $rawKey = $secretKey;
        }

        // Fallback or padding if key is weak (Not recommended for prod but ensures no crash)
        if (strlen($rawKey) !== 32) {
             $rawKey = hash('sha256', $secretKey, true);
        }

        $this->key = new SymmetricKey($rawKey);
    }

    public function load(string $tokenString): ?TokenInterface
    {
        try {
            $parser = Parser::getLocal2($this->key); // Use v2 or v4? Let's use v4 for modern
            // But wait, library might default to v2. Let's check available protocols.
            // Using Version4 directly if strictly required, but Parser::getLocal() usually auto-detects version if possible
            // or we instantiate specific parser.
            
            // Let's use v4 (sodium)
            $parser = Parser::getLocal($this->key, Version4::class);
            $token = $parser->parse($tokenString);

            $payload = $token->getClaims();
            $id = $token->get('jti') ?? md5($tokenString); // JTI or hash
            
            // Check expiration
            $expiresAtStr = $token->getExpiration();
            $expiresAt = null;
            
            if ($expiresAtStr) {
                $expiresAt = new \DateTimeImmutable($expiresAtStr->format(\DateTimeInterface::ATOM));
                if ($expiresAt < new \DateTimeImmutable()) {
                    return null;
                }
            }

            // Extract custom payload
            $customPayload = $payload['data'] ?? [];

            return new class($tokenString, $customPayload, $expiresAt) implements TokenInterface {
                public function __construct(
                    private string $id, // The raw token string itself acts as ID/Value
                    private array $payload,
                    private ?DateTimeInterface $expiresAt
                ) {}

                public function getID(): string { return $this->id; }
                public function getPayload(): array { return $this->payload; }
                public function getExpiresAt(): ?DateTimeInterface { return $this->expiresAt; }
            };

        } catch (\Throwable $e) {
            // Invalid token or decryption failed
            return null;
        }
    }

    public function create(array $payload, DateTimeInterface $expiresAt = null): TokenInterface
    {
        $builder = Builder::getLocal($this->key, new Version4());
        
        $jti = bin2hex(random_bytes(16));
        $builder->set('jti', $jti);
        $builder->set('data', $payload);
        
        if ($expiresAt) {
            $builder->setExpiration($expiresAt);
        } else {
            // Default 7 days if not set
            $builder->setExpiration(new \DateTimeImmutable('+7 days'));
            $expiresAt = new \DateTimeImmutable('+7 days');
        }

        $tokenString = $builder->toString();

        return new class($tokenString, $payload, $expiresAt) implements TokenInterface {
            public function __construct(
                private string $id,
                private array $payload,
                private ?DateTimeInterface $expiresAt
            ) {}

            public function getID(): string { return $this->id; }
            public function getPayload(): array { return $this->payload; }
            public function getExpiresAt(): ?DateTimeInterface { return $this->expiresAt; }
        };
    }

    public function delete(TokenInterface $token): void
    {
        // Stateless: Cannot delete without a blacklist
        // For now, client-side cookie removal is the only action
    }
}
