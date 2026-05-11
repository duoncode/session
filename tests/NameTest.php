<?php

declare(strict_types=1);

namespace Celemas\Session\Tests;

use Celemas\Session\Session;

final class NameTest extends TestCase
{
	public function testNamedSession(): void
	{
		$session = new Session(name: 'test');
		$session->start();

		self::assertSame('test', $session->name());

		$session->destroy();
	}

	public function testUnnamedSession(): void
	{
		$session = new Session();
		$session->start();

		self::assertSame('PHPSESSID', $session->name());

		$session->destroy();
	}
}
