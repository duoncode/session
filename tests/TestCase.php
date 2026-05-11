<?php

declare(strict_types=1);

namespace Celemas\Session\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use SessionHandler;

/**
 * @internal
 *
 * @coversNothing
 */
class TestCase extends BaseTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		session_name('PHPSESSID');
		session_set_save_handler(new SessionHandler(), true);
	}
}
