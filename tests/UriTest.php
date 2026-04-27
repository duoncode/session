<?php

declare(strict_types=1);

namespace Duon\Session\Tests;

use Duon\Session\RuntimeException;
use Duon\Session\Session;
use Duon\Session\Uri;

final class UriTest extends TestCase
{
	private Session $session;
	private Uri $uri;

	protected function setUp(): void
	{
		parent::setUp();

		$this->session = new Session();
		$this->uri = new Uri($this->session);
	}

	protected function tearDown(): void
	{
		if ($this->session->active()) {
			$this->session->destroy();
		}

		parent::tearDown();
	}

	public function testRememberAndPullUri(): void
	{
		$this->session->start();
		$this->uri->remember('/albums?artist=death');

		self::assertSame('/albums?artist=death', $this->uri->pull());
		self::assertSame('/', $this->uri->pull());
	}

	public function testPullReturnsDefaultWhenExpired(): void
	{
		$this->session->start();
		$this->uri->remember('/albums', -3600);

		self::assertSame('/dashboard', $this->uri->pull('/dashboard'));
	}

	public function testCustomStorageKey(): void
	{
		$this->session->start();
		$uri = new Uri($this->session, 'return_to');

		$uri->remember('/albums');

		self::assertTrue($this->session->has('return_to'));
		self::assertFalse($this->session->has(Uri::REMEMBERED));
		self::assertSame('/albums', $uri->pull());
	}

	public function testPullRejectsUnsafeRedirects(): void
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
			$this->uri->remember($uri);

			self::assertSame('/safe', $this->uri->pull('/safe'));
		}
	}

	public function testPullReturnsDefaultForInvalidStorage(): void
	{
		$this->session->start();
		$this->session->set(Uri::REMEMBERED, 'invalid');

		self::assertSame('/safe', $this->uri->pull('/safe'));

		$this->session->set(Uri::REMEMBERED, ['uri' => '/albums']);

		self::assertSame('/safe', $this->uri->pull('/safe'));
	}

	public function testRememberFailsWhenUninitialized(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Session not started');

		$this->uri->remember('/albums');
	}

	public function testPullFailsWhenUninitialized(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Session not started');

		$this->uri->pull();
	}
}
