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
			$this->session->forget();
		}

		parent::tearDown();
	}

	public function testCsrfGetCreatesToken(): void
	{
		$csrf = new Csrf();
		$token = $csrf->get();

		self::assertSame(44, strlen($token));
		self::assertSame($token, $this->session->get('csrftokens')['default']);
	}

	public function testCsrfRequiresActiveSession(): void
	{
		$this->session->forget();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Session not started');

		new Csrf();
	}

	public function testCsrfVerifyPost(): void
	{
		$csrf = new Csrf();
		$token = $csrf->get();

		$_POST['csrftoken'] = $token;

		self::assertTrue($csrf->verify());

		$_POST['csrftoken'] = 'empty words';

		self::assertFalse($csrf->verify());
	}

	public function testCsrfVerifyHeader(): void
	{
		$csrf = new Csrf();
		$token = $csrf->get();

		$_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

		self::assertTrue($csrf->verify());

		$_SERVER['HTTP_X_CSRF_TOKEN'] = 'empty words';

		self::assertFalse($csrf->verify());

		$_SERVER['HTTP_X_CSRF_TOKEN'] = 666;

		self::assertFalse($csrf->verify());
	}

	public function testCsrfVerifyEmptySession(): void
	{
		$csrf = new Csrf();
		$token = $csrf->get();

		$_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
		$_SESSION['csrftokens']['default'] = '';

		self::assertFalse($csrf->verify());
	}

	public function testCsrfVerifyEmptyToken(): void
	{
		$csrf = new Csrf();
		$csrf->get();

		$_POST['csrftoken'] = '';
		self::assertFalse($csrf->verify());

		$_POST['csrftoken'] = '0';
		self::assertFalse($csrf->verify());
	}

	public function testCsrfVerifyTokenNull(): void
	{
		$csrf = new Csrf();

		self::assertFalse($csrf->verify());
	}

	public function testCsrfGetVerifyDifferentPage(): void
	{
		$csrf = new Csrf();
		$tokenDefault = $csrf->get();
		$tokenAlbums = $csrf->get('albums');

		$_POST['csrftoken'] = $tokenDefault;

		self::assertTrue($csrf->verify());
		self::assertFalse($csrf->verify('albums'));

		$_POST['csrftoken'] = $tokenAlbums;

		self::assertFalse($csrf->verify());
		self::assertTrue($csrf->verify('albums'));
	}
}
