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
		unset(
			$_POST['_token'],
			$_POST['csrf'],
			$_SERVER['HTTP_X_CSRF_TOKEN'],
			$_SERVER['HTTP_X_APP_CSRF'],
			$_SERVER['HTTP_X_SERVER_CSRF'],
			$_SESSION['duon_csrf_tokens'],
			$_SESSION['tokens'],
			$_SESSION['server_tokens'],
		);

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
		self::assertSame($token, $this->session->get('duon_csrf_tokens')['default']);
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
		$this->session->set('duon_csrf_tokens', 'invalid');

		$token = $csrf->token();

		self::assertSame($token, $this->session->get('duon_csrf_tokens')['default']);
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
		self::assertArrayNotHasKey('albums', $this->session->get('duon_csrf_tokens'));
	}

	public function testCsrfVerifyPost(): void
	{
		$csrf = new Csrf($this->session);
		$token = $csrf->token();

		$_POST['_token'] = $token;

		self::assertTrue($csrf->verify());

		$_POST['_token'] = 'empty words';

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

	public function testCsrfCustomKeys(): void
	{
		$csrf = new Csrf(
			$this->session,
			key: 'tokens',
			field: 'csrf',
			header: 'X-App-CSRF',
		);
		$token = $csrf->token();

		self::assertSame($token, $this->session->get('tokens')['default']);

		$_POST['csrf'] = $token;
		self::assertTrue($csrf->verify());

		unset($_POST['csrf']);
		$_SERVER['HTTP_X_APP_CSRF'] = $token;
		self::assertTrue($csrf->verify());

		$serverKeyCsrf = new Csrf(
			$this->session,
			key: 'server_tokens',
			header: 'HTTP_X_SERVER_CSRF',
		);
		$serverToken = $serverKeyCsrf->token();

		$_SERVER['HTTP_X_SERVER_CSRF'] = $serverToken;
		self::assertTrue($serverKeyCsrf->verify());
	}

	public function testCsrfVerifyEmptySession(): void
	{
		$csrf = new Csrf($this->session);
		$token = $csrf->token();

		$_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
		$_SESSION['duon_csrf_tokens']['default'] = '';

		self::assertFalse($csrf->verify());
	}

	public function testCsrfVerifyEmptyToken(): void
	{
		$csrf = new Csrf($this->session);
		$csrf->token();

		$_POST['_token'] = '';
		self::assertFalse($csrf->verify());

		$_POST['_token'] = '0';
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
		self::assertArrayNotHasKey('missing', $_SESSION['duon_csrf_tokens']);
	}

	public function testCsrfTokenVerifyDifferentPage(): void
	{
		$csrf = new Csrf($this->session);
		$tokenDefault = $csrf->token();
		$tokenAlbums = $csrf->token('albums');

		$_POST['_token'] = $tokenDefault;

		self::assertTrue($csrf->verify());
		self::assertFalse($csrf->verify('albums'));

		$_POST['_token'] = $tokenAlbums;

		self::assertFalse($csrf->verify());
		self::assertTrue($csrf->verify('albums'));
	}
}
