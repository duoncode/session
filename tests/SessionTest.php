<?php

declare(strict_types=1);

namespace Duon\Session\Tests;

use Duon\Session\Flash;
use Duon\Session\OutOfBoundsException;
use Duon\Session\RuntimeException;
use Duon\Session\Session;

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
		self::assertSame('1', ini_get('session.use_only_cookies'));
		self::assertSame('1', ini_get('session.use_strict_mode'));
		self::assertFalse((bool) ini_get('session.use_trans_sid'));
	}

	public function testSessionOptionsOverrideDefaults(): void
	{
		$session = new Session(options: [
			'cache_limiter' => '',
			'cookie_samesite' => 'Strict',
		]);
		$session->start();
		$params = session_get_cookie_params();

		self::assertSame('', session_cache_limiter());
		self::assertTrue($params['httponly']);
		self::assertSame('Strict', $params['samesite']);
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

	public function testFlashCanBeUsedDirectly(): void
	{
		$this->session->start();
		$flash = new Flash($this->session);

		$flash->add('Your existence is a script');

		self::assertTrue($flash->has());
		self::assertSame('Your existence is a script', $flash->pop()[0]['message']);
	}

	public function testFlashMessagesAll(): void
	{
		$this->session->start();
		self::assertFalse($this->session->flash->has());

		$this->session->flash->add('Your existence is a script');
		$this->session->flash->add('Time is a thing we must accept', 'error');

		self::assertTrue($this->session->flash->has());
		self::assertTrue($this->session->flash->has('error'));
		self::assertFalse($this->session->flash->has('info'));

		$flashes = $this->session->flash->pop();
		self::assertCount(2, $flashes);
		self::assertSame('default', $flashes[0]['queue']);
		self::assertSame('error', $flashes[1]['queue']);
	}

	public function testFlashMessagesQueue(): void
	{
		$this->session->start();
		self::assertFalse($this->session->flash->has());

		$this->session->flash->add('Your existence is a script');
		$this->session->flash->add('Time is a thing we must accept', 'error');

		$flashes = $this->session->flash->pop('error');
		self::assertCount(1, $flashes);
		self::assertSame('error', $flashes[0]['queue']);

		$flashes = $this->session->flash->pop();
		self::assertCount(1, $flashes);
		self::assertSame('default', $flashes[0]['queue']);
	}

	public function testFlashMessagesAreReturnedRaw(): void
	{
		$this->session->start();
		$this->session->flash->add('<strong>Saved</strong>', 'alerts&info');

		$flashes = $this->session->flash->pop();

		self::assertSame('<strong>Saved</strong>', $flashes[0]['message']);
		self::assertSame('alerts&info', $flashes[0]['queue']);
	}

	public function testPopFlashesQueueReturnsEmptyWhenUnset(): void
	{
		$this->session->start();
		unset($_SESSION[Flash::STORAGE]);

		$flashes = $this->session->flash->pop('error');

		self::assertSame([], $flashes);
		self::assertSame([], $_SESSION[Flash::STORAGE]);
	}

	public function testFlashIgnoresInvalidStorage(): void
	{
		$this->session->start();
		$this->session->set(Flash::STORAGE, 'invalid');

		self::assertFalse($this->session->flash->has());
	}

	public function testFlashIgnoresInvalidEntries(): void
	{
		$this->session->start();
		$this->session->set(Flash::STORAGE, [
			'invalid',
			['message' => 'Saved.', 'queue' => 'default'],
		]);

		$flashes = $this->session->flash->pop();

		self::assertCount(1, $flashes);
		self::assertSame('Saved.', $flashes[1]['message']);
	}

	public function testFlashMessagesFailWhenUninitialized(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Session not started');

		$this->session->flash->add('Your existence is a script');
	}

	public function testRememberUri(): void
	{
		$this->session->start();
		$this->session->rememberUri('/albums?artist=death');

		self::assertSame('/albums?artist=death', $this->session->rememberedUri());
		self::assertSame('/', $this->session->rememberedUri());

		$this->session->rememberUri('/albums', -3600);
		self::assertSame('/', $this->session->rememberedUri());
	}

	public function testRememberUriRejectsUnsafeRedirects(): void
	{
		$this->session->start();

		foreach ([
			'',
			'albums',
			'https://www.example.com/albums',
			'ftp://www.example.com/albums',
			'javascript://%0Aalert(1)',
			'//www.example.com/albums',
			'/%2Fwww.example.com/albums',
			'/\\www.example.com/albums',
			'/%5Cwww.example.com/albums',
			"/albums\nLocation: https://www.example.com",
			'/albums%0ALocation:%20https://www.example.com',
		] as $uri) {
			$this->session->rememberUri($uri);

			self::assertSame('/', $this->session->rememberedUri());
		}
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
