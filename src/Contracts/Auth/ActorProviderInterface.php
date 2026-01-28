<?php

declare(strict_types=1);

namespace Witals\Framework\Contracts\Auth;

interface ActorProviderInterface
{
    /**
     * Get actor based on token.
     */
    public function getActor(TokenInterface $token): ?object;
}
