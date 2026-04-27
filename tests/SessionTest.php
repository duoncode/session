<?php

declare(strict_types=1);

namespace Duon\Session\Tests;

use Duon\Session\Contract\Helpers as HelpersContract;
use Duon\Session\Csrf;
use Duon\Session\Flash;
use Duon\Session\OutOfBoundsException;
use Duon\Session\RuntimeException;
use Duon\Session\Session;
use Duon\Session\Uri;

final class SessionTest extends TestCase
{
	private Session $session;

	protected function setUp(): void
	{
		parent::setUp();

		$this->session = new Session();
	}

	protected function tearDown(): void
	{
		if ($this->session->active()) {
			$this->session->destroy();
		}

		parent::tearDown();
	}

	public function testSessionSetHasGetNameId(): void
	{
		$this->session->start();
		$this->session->set('Chuck', 'Schuldiner');

		self::assertTrue($this->session->has('Chuck'));
		self::assertSame('Schuldiner', $this->session->get('Chuck'));
		self::assertSame('PHPSESSID', $this->session->name());
		self::assertNotEmpty($this->session->id());
	}

	public function testStartIsIdempotentWhenAlreadyActive(): void
	{
		$this->session->start();
		$id = $this->session->id();

		$this->session->start();

		self::assertTrue($this->session->active());
		self::assertSame($id, $this->session->id());
	}

	public function testSessionUsesSecureDefaults(): void
	{
		$this->session->start();
		$params = session_get_cookie_params();

		self::assertSame('nocache', session_cache_limiter());
		self::assertTrue($params['httponly']);
		self::assertSame('Lax', $params['samesite']);
		self::assertTrue($params['secure']);
		self::assertSame('1', ini_get('session.use_only_cookies'));
		self::assertSame('1', ini_get('session.use_strict_mode'));
		self::assertFalse((bool) ini_get('session.use_trans_sid'));
	}

	public function testSessionOptionsOverrideDefaults(): void
	{
		$session = new Session(options: [
			'cache_limiter' => '',
			'cookie_samesite' => 'Strict',
			'cookie_secure' => false,
		]);
		$session->start();
		$params = session_get_cookie_params();

		self::assertSame('', session_cache_limiter());
		self::assertTrue($params['httponly']);
		self::assertSame('Strict', $params['samesite']);
		self::assertFalse($params['secure']);
	}

	public function testSetFailsWhenUninitialized(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Session not started');

		$this->session->set('Chuck', 'Schuldiner');
	}

	public function testSessionAllFailsWhenUninitialized(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Session not started');

		$this->session->all();
	}

	public function testSessionAllClearRemovePull(): void
	{
		$this->session->start();
		$this->session->set('Chuck', 'Schuldiner');
		$this->session->set('Rick', 'Rozz');

		self::assertSame(
			[
				'Chuck' => 'Schuldiner',
				'Rick' => 'Rozz',
			],
			$this->session->all(),
		);

		$this->session->remove('Rick');
		self::assertFalse($this->session->has('Rick'));

		self::assertSame('Schuldiner', $this->session->pull('Chuck'));
		self::assertFalse($this->session->has('Chuck'));
		self::assertSame('guest', $this->session->pull('missing', 'guest'));

		$this->session->set('James', 'Murphy');
		$this->session->clear();

		self::assertSame([], $this->session->all());
	}

	public function testSessionPullThrowsWhenMissing(): void
	{
		$this->session->start();

		$this->expectException(OutOfBoundsException::class);
		$this->expectExceptionMessage(
			"The session key 'To exist in this world may be a mistake' does not exist",
		);

		$this->session->pull('To exist in this world may be a mistake');
	}

	public function testSessionThrowsWhenMissing(): void
	{
		$this->session->start();

		$this->expectException(OutOfBoundsException::class);
		$this->expectExceptionMessage(
			"The session key 'To exist in this world may be a mistake' does not exist",
		);

		$this->session->get('To exist in this world may be a mistake');
	}

	public function testSessionGetDefault(): void
	{
		$this->session->start();

		self::assertSame('Rozz', $this->session->get('Rick', 'Rozz'));
	}

	public function testSessionFlashPropertyReturnsSameInstance(): void
	{
		self::assertInstanceOf(Flash::class, $this->session->flash);
		self::assertSame($this->session->flash, $this->session->flash);
	}

	public function testSessionCsrfPropertyReturnsSameInstance(): void
	{
		$this->session->start();

		self::assertInstanceOf(Csrf::class, $this->session->csrf);
		self::assertSame($this->session->csrf, $this->session->csrf);
	}

	public function testSessionUriPropertyReturnsSameInstance(): void
	{
		self::assertInstanceOf(Uri::class, $this->session->uri);
		self::assertSame($this->session->uri, $this->session->uri);
	}

	public function testSessionUsesCustomHelpers(): void
	{
		$session = new Session(helpers: new class implements HelpersContract {
			#[\Override]
			public function flash(Session $session): Flash
			{
				return new Flash($session, key: 'flashes');
			}

			#[\Override]
			public function csrf(Session $session): Csrf
			{
				return new Csrf($session, key: 'tokens');
			}

			#[\Override]
			public function uri(Session $session): Uri
			{
				return new Uri($session, key: 'return_to');
			}
		});
		$session->start();

		$session->flash->add('Saved.');
		$session->csrf->token();
		$session->uri->remember('/account');

		self::assertTrue($session->has('flashes'));
		self::assertTrue($session->has('tokens'));
		self::assertTrue($session->has('return_to'));

		$session->destroy();
	}

	public function testSessionRunStartDestroy(): void
	{
		$this->session->start();
		$this->session->set('Chuck', 'Schuldiner');

		self::assertSame('Schuldiner', $this->session->get('Chuck'));

		$this->session->destroy();

		self::assertFalse($this->session->active());
	}

	public function testDestroyFailsWhenUninitialized(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Session not started');

		$this->session->destroy();
	}

	public function testCloseWritesAndEndsSession(): void
	{
		$this->session->start();
		$this->session->set('Chuck', 'Schuldiner');
		$this->session->close();

		self::assertFalse($this->session->active());

		$this->session->start();
		self::assertSame('Schuldiner', $this->session->get('Chuck'));
	}

	public function testGetFailsAfterClose(): void
	{
		$this->session->start();
		$this->session->set('Chuck', 'Schuldiner');
		$this->session->close();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Session not started');

		$this->session->get('Chuck');
	}

	public function testCloseFailsWhenUninitialized(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Session not started');

		$this->session->close();
	}

	public function testRegenerateFailsWhenUninitialized(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Session not started');

		$this->session->regenerate();
	}

	public function testRegenerateId(): void
	{
		$this->session->start();
		$id = session_id();
		$this->session->regenerate();

		self::assertNotSame($id, session_id());
	}
}
