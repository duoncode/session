<?php

declare(strict_types=1);

namespace Duon\Session\Tests;

use Duon\Session\Session;

final class NameTest extends TestCase
{
	public function testNamedSession(): void
	{
		$session = new Session('test');
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
