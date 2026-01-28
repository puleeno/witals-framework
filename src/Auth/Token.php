<?php

declare(strict_types=1);

namespace Witals\Framework\Auth;

use Witals\Framework\Contracts\Auth\TokenInterface;
use DateTimeInterface;

class Token implements TokenInterface
{
    public function __construct(
        protected string $id,
        protected array $payload,
        protected ?DateTimeInterface $expiresAt = null
    ) {
    }

    public function getID(): string
    {
        return $this->id;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expiresAt;
    }
}
