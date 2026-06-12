<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Session;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Session\NativeSession;

class NativeSessionTest extends TestCase
{
    private NativeSession $session;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        $this->session = new NativeSession();
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }

    public function testStartStartsSession(): void
    {
        $this->session->start();

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
    }

    public function testStartDoesNotStartIfAlreadyStarted(): void
    {
        $this->session->start();
        $sessionId1 = session_id();

        $this->session->start();
        $sessionId2 = session_id();

        $this->assertSame($sessionId1, $sessionId2);
    }

    public function testSetStoresValue(): void
    {
        $this->session->set('key', 'value');

        $this->assertSame('value', $_SESSION['key']);
    }

    public function testSetStartsSession(): void
    {
        $this->session->set('key', 'value');

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
    }

    public function testGetReturnsValue(): void
    {
        $_SESSION['key'] = 'value';

        $result = $this->session->get('key');

        $this->assertSame('value', $result);
    }

    public function testGetReturnsDefaultWhenKeyNotFound(): void
    {
        $result = $this->session->get('nonexistent', 'default');

        $this->assertSame('default', $result);
    }

    public function testGetReturnsNullWhenKeyNotFoundAndNoDefault(): void
    {
        $result = $this->session->get('nonexistent');

        $this->assertNull($result);
    }

    public function testGetStartsSession(): void
    {
        $this->session->get('key');

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
    }

    public function testHasReturnsTrueWhenKeyExists(): void
    {
        $_SESSION['key'] = 'value';

        $this->assertTrue($this->session->has('key'));
    }

    public function testHasReturnsFalseWhenKeyNotExists(): void
    {
        $this->assertFalse($this->session->has('nonexistent'));
    }

    public function testHasStartsSession(): void
    {
        $this->session->has('key');

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
    }

    public function testRemoveDeletesKey(): void
    {
        $_SESSION['key'] = 'value';
        $this->session->remove('key');

        $this->assertArrayNotHasKey('key', $_SESSION);
    }

    public function testRemoveStartsSession(): void
    {
        $this->session->remove('key');

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
    }

    public function testClearEmptiesSession(): void
    {
        $_SESSION['key1'] = 'value1';
        $_SESSION['key2'] = 'value2';
        $this->session->clear();

        $this->assertEmpty($_SESSION);
    }

    public function testClearStartsSession(): void
    {
        $this->session->clear();

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
    }

    public function testDestroyDestroysSession(): void
    {
        $this->session->start();
        $_SESSION['key'] = 'value';
        $this->session->destroy();

        $this->assertEmpty($_SESSION);
        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    public function testDestroyDoesNothingIfNotStarted(): void
    {
        $this->session->destroy();

        $this->expectNotToPerformAssertions();
    }

    public function testGetIdReturnsSessionId(): void
    {
        $this->session->start();

        $id = $this->session->getId();

        $this->assertSame(session_id(), $id);
    }

    public function testGetIdStartsSession(): void
    {
        $this->session->getId();

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
    }

    public function testRegenerateChangesSessionId(): void
    {
        $this->session->start();
        $oldId = session_id();

        $this->session->regenerate();
        $newId = session_id();

        $this->assertNotSame($oldId, $newId);
    }

    public function testRegenerateStartsSession(): void
    {
        $this->session->regenerate();

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
    }

    public function testMultipleOperationsWork(): void
    {
        $this->session->set('key1', 'value1');
        $this->session->set('key2', 'value2');

        $this->assertTrue($this->session->has('key1'));
        $this->assertTrue($this->session->has('key2'));

        $this->assertSame('value1', $this->session->get('key1'));
        $this->assertSame('value2', $this->session->get('key2'));

        $this->session->remove('key1');

        $this->assertFalse($this->session->has('key1'));
        $this->assertTrue($this->session->has('key2'));
    }

    public function testImplementsSessionInterface(): void
    {
        $this->assertInstanceOf(\Witals\Framework\Contracts\Session\SessionInterface::class, $this->session);
    }
}
