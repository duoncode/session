<?php

declare(strict_types=1);

namespace Duon\Session\Tests;

use Duon\Session\Csrf;
use Duon\Session\RuntimeException;
use Duon\Session\Session;

final class CsrfTest extends TestCase
{
	private Session $session;

	protected function setUp(): void
	{
		parent::setUp();

		$this->session = new Session();
		$this->session->start();
	}

	protected function tearDown(): void
	{
		unset($_POST['csrftoken'], $_SERVER['HTTP_X_CSRF_TOKEN'], $_SESSION['csrftokens']);

		if ($this->session->active()) {
			$this->session->destroy();
		}

		parent::tearDown();
	}

	public function testCsrfTokenCreatesToken(): void
	{
		$csrf = new Csrf($this->session);
		$token = $csrf->token();

		self::assertSame(44, strlen($token));
		self::assertSame($token, $this->session->get('csrftokens')['default']);
	}

	public function testCsrfTokenReturnsExistingToken(): void
	{
		$csrf = new Csrf($this->session);
		$token = $csrf->token();

		self::assertSame($token, $csrf->token());
	}

	public function testCsrfTokenReplacesInvalidStorage(): void
	{
		$csrf = new Csrf($this->session);
		$this->session->set('csrftokens', 'invalid');

		$token = $csrf->token();

		self::assertSame($token, $this->session->get('csrftokens')['default']);
	}

	public function testCsrfRequiresActiveSession(): void
	{
		$this->session->destroy();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Session not started');

		new Csrf($this->session);
	}

	public function testCsrfRefreshReplacesToken(): void
	{
		$csrf = new Csrf($this->session);
		$token = $csrf->token();
		$newToken = $csrf->refresh();

		self::assertNotSame($token, $newToken);
		self::assertFalse($csrf->verify(token: $token));
		self::assertTrue($csrf->verify(token: $newToken));
	}

	public function testCsrfRemoveDeletesToken(): void
	{
		$csrf = new Csrf($this->session);
		$token = $csrf->token('albums');

		$csrf->remove('missing');
		self::assertTrue($csrf->verify('albums', $token));

		$csrf->remove('albums');

		self::assertFalse($csrf->verify('albums', $token));
		self::assertArrayNotHasKey('albums', $this->session->get('csrftokens'));
	}

	public function testCsrfVerifyPost(): void
	{
		$csrf = new Csrf($this->session);
		$token = $csrf->token();

		$_POST['csrftoken'] = $token;

		self::assertTrue($csrf->verify());

		$_POST['csrftoken'] = 'empty words';

		self::assertFalse($csrf->verify());
	}

	public function testCsrfVerifyHeader(): void
	{
		$csrf = new Csrf($this->session);
		$token = $csrf->token();

		$_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

		self::assertTrue($csrf->verify());

		$_SERVER['HTTP_X_CSRF_TOKEN'] = 'empty words';

		self::assertFalse($csrf->verify());

		$_SERVER['HTTP_X_CSRF_TOKEN'] = 666;

		self::assertFalse($csrf->verify());
	}

	public function testCsrfVerifyEmptySession(): void
	{
		$csrf = new Csrf($this->session);
		$token = $csrf->token();

		$_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
		$_SESSION['csrftokens']['default'] = '';

		self::assertFalse($csrf->verify());
	}

	public function testCsrfVerifyEmptyToken(): void
	{
		$csrf = new Csrf($this->session);
		$csrf->token();

		$_POST['csrftoken'] = '';
		self::assertFalse($csrf->verify());

		$_POST['csrftoken'] = '0';
		self::assertFalse($csrf->verify());
	}

	public function testCsrfVerifyTokenNull(): void
	{
		$csrf = new Csrf($this->session);

		self::assertFalse($csrf->verify());
	}

	public function testCsrfVerifyDoesNotCreateToken(): void
	{
		$csrf = new Csrf($this->session);

		self::assertFalse($csrf->verify('missing', 'submitted'));
		self::assertArrayNotHasKey('missing', $_SESSION['csrftokens']);
	}

	public function testCsrfTokenVerifyDifferentPage(): void
	{
		$csrf = new Csrf($this->session);
		$tokenDefault = $csrf->token();
		$tokenAlbums = $csrf->token('albums');

		$_POST['csrftoken'] = $tokenDefault;

		self::assertTrue($csrf->verify());
		self::assertFalse($csrf->verify('albums'));

		$_POST['csrftoken'] = $tokenAlbums;

		self::assertFalse($csrf->verify());
		self::assertTrue($csrf->verify('albums'));
	}
}
