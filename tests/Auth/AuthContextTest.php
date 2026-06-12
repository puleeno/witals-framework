<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Auth;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Auth\AuthContext;
use Witals\Framework\Auth\Token;
use Witals\Framework\Contracts\Auth\ActorProviderInterface;
use Witals\Framework\Contracts\Auth\TokenInterface;
use DateTime;

class AuthContextTest extends TestCase
{
    private AuthContext $authContext;
    private ActorProviderInterface $actorProvider;

    protected function setUp(): void
    {
        $this->actorProvider = $this->createMock(ActorProviderInterface::class);
        $this->authContext = new AuthContext($this->actorProvider);
    }

    public function testStartSetsTokenAndActor(): void
    {
        $token = new Token('token123', ['user_id' => 1]);
        $actor = (object) ['id' => 1, 'name' => 'Test User'];

        $this->authContext->start($token, $actor);

        $this->assertSame($token, $this->authContext->getToken());
        $this->assertSame($actor, $this->authContext->getActor());
        $this->assertFalse($this->authContext->isClosed());
    }

    public function testStartWithoutActor(): void
    {
        $token = new Token('token123', ['user_id' => 1]);

        $this->authContext->start($token);

        $this->assertSame($token, $this->authContext->getToken());
        $this->assertNull($this->authContext->getActor());
    }

    public function testGetTokenReturnsNullWhenClosed(): void
    {
        $token = new Token('token123', ['user_id' => 1]);
        $this->authContext->start($token);
        $this->authContext->close();

        $this->assertNull($this->authContext->getToken());
    }

    public function testGetActorReturnsNullWhenClosed(): void
    {
        $token = new Token('token123', ['user_id' => 1]);
        $actor = (object) ['id' => 1, 'name' => 'Test User'];
        $this->authContext->start($token, $actor);
        $this->authContext->close();

        $this->assertNull($this->authContext->getActor());
    }

    public function testGetActorLoadsFromProviderWhenNotSet(): void
    {
        $token = new Token('token123', ['user_id' => 1]);
        $actor = (object) ['id' => 1, 'name' => 'Test User'];

        $this->actorProvider
            ->expects($this->once())
            ->method('getActor')
            ->with($token)
            ->willReturn($actor);

        $this->authContext->start($token);
        $retrievedActor = $this->authContext->getActor();

        $this->assertSame($actor, $retrievedActor);
    }

    public function testGetActorDoesNotLoadFromProviderWhenAlreadySet(): void
    {
        $token = new Token('token123', ['user_id' => 1]);
        $actor = (object) ['id' => 1, 'name' => 'Test User'];

        $this->actorProvider
            ->expects($this->never())
            ->method('getActor');

        $this->authContext->start($token, $actor);
        $retrievedActor = $this->authContext->getActor();

        $this->assertSame($actor, $retrievedActor);
    }

    public function testGetActorDoesNotLoadFromProviderWhenTokenIsNull(): void
    {
        $this->actorProvider
            ->expects($this->never())
            ->method('getActor');

        $actor = $this->authContext->getActor();

        $this->assertNull($actor);
    }

    public function testCloseClearsTokenAndActor(): void
    {
        $token = new Token('token123', ['user_id' => 1]);
        $actor = (object) ['id' => 1, 'name' => 'Test User'];
        $this->authContext->start($token, $actor);

        $this->authContext->close();

        $this->assertNull($this->authContext->getToken());
        $this->assertNull($this->authContext->getActor());
        $this->assertTrue($this->authContext->isClosed());
    }

    public function testIsClosedReturnsFalseInitially(): void
    {
        $this->assertFalse($this->authContext->isClosed());
    }

    public function testIsClosedReturnsTrueAfterClose(): void
    {
        $this->authContext->close();
        $this->assertTrue($this->authContext->isClosed());
    }

    public function testCanRestartAfterClose(): void
    {
        $token1 = new Token('token123', ['user_id' => 1]);
        $actor1 = (object) ['id' => 1, 'name' => 'Test User'];

        $this->authContext->start($token1, $actor1);
        $this->authContext->close();

        $token2 = new Token('token456', ['user_id' => 2]);
        $actor2 = (object) ['id' => 2, 'name' => 'Another User'];

        $this->authContext->start($token2, $actor2);

        $this->assertSame($token2, $this->authContext->getToken());
        $this->assertSame($actor2, $this->authContext->getActor());
        $this->assertFalse($this->authContext->isClosed());
    }

    public function testConstructWithoutActorProvider(): void
    {
        $authContext = new AuthContext();
        $token = new Token('token123', ['user_id' => 1]);

        $authContext->start($token);

        $this->assertSame($token, $authContext->getToken());
        $this->assertNull($authContext->getActor());
    }
}
