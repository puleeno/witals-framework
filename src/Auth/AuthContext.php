<?php

declare(strict_types=1);

namespace Witals\Framework\Auth;

use Witals\Framework\Contracts\Auth\AuthContextInterface;
use Witals\Framework\Contracts\Auth\TokenInterface;
use Witals\Framework\Contracts\Auth\ActorProviderInterface;

class AuthContext implements AuthContextInterface
{
    protected ?TokenInterface $token = null;
    protected ?object $actor = null;
    protected bool $closed = false;

    public function __construct(
        protected ?ActorProviderInterface $actorProvider = null
    ) {
    }

    public function start(TokenInterface $token, ?object $actor = null): void
    {
        $this->token = $token;
        $this->actor = $actor;
        $this->closed = false;
    }

    public function getToken(): ?TokenInterface
    {
        if ($this->closed) {
            return null;
        }
        return $this->token;
    }

    public function getActor(): ?object
    {
        if ($this->closed) {
            return null;
        }

        if ($this->actor === null && $this->token !== null && $this->actorProvider !== null) {
            $this->actor = $this->actorProvider->getActor($this->token);
        }

        return $this->actor;
    }

    public function close(): void
    {
        $this->token = null;
        $this->actor = null;
        $this->closed = true;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }
}
