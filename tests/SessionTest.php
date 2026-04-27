<?php

declare(strict_types=1);

namespace Duon\Session\Tests;

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
			$this->session->forget();
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

	public function testSetFailsWhenUninitialized(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Session not started');

		$this->session->set('Chuck', 'Schuldiner');
	}

	public function testSessionUnset(): void
	{
		$this->session->start();
		$this->session->set('Chuck', 'Schuldiner');

		self::assertSame('Schuldiner', $this->session->get('Chuck'));
		self::assertTrue($this->session->has('Chuck'));

		$this->session->unset('Chuck');

		self::assertNull($this->session->get('Chuck', null));
		self::assertFalse($this->session->has('Chuck'));
	}

	public function testSessionThrowsWhenMissing(): void
	{
		$this->expectException(OutOfBoundsException::class);
		$this->expectExceptionMessage(
			"The session key 'To exist in this world may be a mistake' does not exist",
		);

		$this->session->get('To exist in this world may be a mistake');
	}

	public function testSessionGetDefault(): void
	{
		self::assertSame('Rozz', $this->session->get('Rick', 'Rozz'));
	}

	public function testFlashMessagesAll(): void
	{
		$this->session->start();
		self::assertFalse($this->session->hasFlashes());

		$this->session->flash('Your existence is a script');
		$this->session->flash('Time is a thing we must accept', 'error');

		self::assertTrue($this->session->hasFlashes());
		self::assertTrue($this->session->hasFlashes('error'));
		self::assertFalse($this->session->hasFlashes('info'));

		$flashes = $this->session->popFlashes();
		self::assertCount(2, $flashes);
		self::assertSame('default', $flashes[0]['queue']);
		self::assertSame('error', $flashes[1]['queue']);
	}

	public function testFlashMessagesQueue(): void
	{
		$this->session->start();
		self::assertFalse($this->session->hasFlashes());

		$this->session->flash('Your existence is a script');
		$this->session->flash('Time is a thing we must accept', 'error');

		$flashes = $this->session->popFlashes('error');
		self::assertCount(1, $flashes);
		self::assertSame('error', $flashes[0]['queue']);

		$flashes = $this->session->popFlashes();
		self::assertCount(1, $flashes);
		self::assertSame('default', $flashes[0]['queue']);
	}

	public function testPopFlashesQueueReturnsEmptyWhenUnset(): void
	{
		unset($_SESSION[Session::FLASH]);

		$flashes = $this->session->popFlashes('error');

		self::assertSame([], $flashes);
		self::assertSame([], $_SESSION[Session::FLASH]);
	}

	public function testFlashMessagesFailWhenUninitialized(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Session not started');

		$this->session->flash('Your existence is a script');
	}

	public function testRememberUri(): void
	{
		$this->session->rememberUri('https://www.example.com/albums');

		self::assertSame('https://www.example.com/albums', $this->session->rememberedUri());
		self::assertSame('/', $this->session->rememberedUri());

		$this->session->rememberUri('https://www.example.com/albums', -3600);
		self::assertSame('/', $this->session->rememberedUri());
	}

	public function testSessionRunStartForget(): void
	{
		$this->session->start();
		$this->session->set('Chuck', 'Schuldiner');

		self::assertSame('Schuldiner', $this->session->get('Chuck'));

		$this->session->forget();

		self::assertFalse($this->session->has('Chuck'));
	}

	public function testRegenerateId(): void
	{
		$this->session->start();
		$id = session_id();
		$this->session->regenerate();

		self::assertNotSame($id, session_id());
	}
}
