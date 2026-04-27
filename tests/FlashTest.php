<?php

declare(strict_types=1);

namespace Duon\Session\Tests;

use Duon\Session\Flash;
use Duon\Session\RuntimeException;
use Duon\Session\Session;

final class FlashTest extends TestCase
{
	private Session $session;
	private Flash $flash;

	protected function setUp(): void
	{
		parent::setUp();

		$this->session = new Session();
		$this->flash = new Flash($this->session);
	}

	protected function tearDown(): void
	{
		if ($this->session->active()) {
			$this->session->destroy();
		}

		parent::tearDown();
	}

	public function testFlashCanBeUsedDirectly(): void
	{
		$this->session->start();

		$this->flash->add('Your existence is a script');

		self::assertTrue($this->flash->has());
		self::assertSame('Your existence is a script', $this->flash->pop()[0]['message']);
	}

	public function testFlashMessagesAll(): void
	{
		$this->session->start();
		self::assertFalse($this->flash->has());

		$this->flash->add('Your existence is a script');
		$this->flash->add('Time is a thing we must accept', 'error');

		self::assertTrue($this->flash->has());
		self::assertTrue($this->flash->has('error'));
		self::assertFalse($this->flash->has('info'));

		$flashes = $this->flash->pop();
		self::assertCount(2, $flashes);
		self::assertSame('default', $flashes[0]['queue']);
		self::assertSame('error', $flashes[1]['queue']);
	}

	public function testFlashMessagesQueue(): void
	{
		$this->session->start();
		self::assertFalse($this->flash->has());

		$this->flash->add('Your existence is a script');
		$this->flash->add('Time is a thing we must accept', 'error');

		$flashes = $this->flash->pop('error');
		self::assertCount(1, $flashes);
		self::assertSame('error', $flashes[0]['queue']);

		$flashes = $this->flash->pop();
		self::assertCount(1, $flashes);
		self::assertSame('default', $flashes[0]['queue']);
	}

	public function testFlashMessagesCanBePeeked(): void
	{
		$this->session->start();
		$this->flash->add('Your existence is a script');
		$this->flash->add('Time is a thing we must accept', 'error');

		$errors = $this->flash->peek('error');
		self::assertCount(1, $errors);
		self::assertSame('error', $errors[0]['queue']);

		$flashes = $this->flash->peek();
		self::assertCount(2, $flashes);
		self::assertTrue($this->flash->has('error'));
	}

	public function testFlashMessagesCanBeCleared(): void
	{
		$this->session->start();
		$this->flash->add('Your existence is a script');
		$this->flash->add('Time is a thing we must accept', 'error');

		$this->flash->clear('error');

		self::assertFalse($this->flash->has('error'));
		self::assertTrue($this->flash->has());

		$this->flash->clear();

		self::assertFalse($this->flash->has());
	}

	public function testFlashMessagesAreReturnedRaw(): void
	{
		$this->session->start();
		$this->flash->add('<strong>Saved</strong>', 'alerts&info');

		$flashes = $this->flash->pop();

		self::assertSame('<strong>Saved</strong>', $flashes[0]['message']);
		self::assertSame('alerts&info', $flashes[0]['queue']);
	}

	public function testPopFlashesQueueReturnsEmptyWhenUnset(): void
	{
		$this->session->start();
		unset($_SESSION[Flash::STORAGE]);

		$flashes = $this->flash->pop('error');

		self::assertSame([], $flashes);
		self::assertSame([], $_SESSION[Flash::STORAGE]);
	}

	public function testFlashIgnoresInvalidStorage(): void
	{
		$this->session->start();
		$this->session->set(Flash::STORAGE, 'invalid');

		self::assertFalse($this->flash->has());
	}

	public function testFlashIgnoresInvalidEntries(): void
	{
		$this->session->start();
		$this->session->set(Flash::STORAGE, [
			'invalid',
			['message' => 'Saved.', 'queue' => 'default'],
		]);

		$flashes = $this->flash->pop();

		self::assertCount(1, $flashes);
		self::assertSame('Saved.', $flashes[1]['message']);
	}

	public function testFlashMessagesFailWhenUninitialized(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Session not started');

		$this->flash->add('Your existence is a script');
	}
}
