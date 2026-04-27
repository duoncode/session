<?php

declare(strict_types=1);

namespace Duon\Session {
	function session_set_save_handler($handler, $registerShutdown = true): bool
	{
		return \Duon\Session\Tests\HandlerTest::sessionSetSaveHandlerResult(
			$handler,
			$registerShutdown,
		);
	}
}

namespace Duon\Session\Tests {
	use Duon\Session\RuntimeException;
	use Duon\Session\Session;

	final class HandlerTest extends TestCase
	{
		private static bool $forceHandlerFalse = false;

		public static function sessionSetSaveHandlerResult($handler, $registerShutdown): bool
		{
			if (self::$forceHandlerFalse) {
				return false;
			}

			return \session_set_save_handler($handler, (bool) $registerShutdown);
		}

		protected function tearDown(): void
		{
			self::$forceHandlerFalse = false;

			if (session_status() === PHP_SESSION_ACTIVE) {
				session_unset();
				session_destroy();
			}

			parent::tearDown();
		}

		public function testCustomHandler(): void
		{
			$handler = new TestSessionHandler();
			$session = new Session(name: 'custom', handler: $handler);
			$session->start();
			$session->set('test', 'value');

			self::assertSame('custom', $session->name());
			self::assertSame('value', $session->get('test'));
			self::assertTrue($handler->visited);

			$session->destroy();
		}

		public function testCustomHandlerSetupFailureThrows(): void
		{
			self::$forceHandlerFalse = true;
			$handler = new TestSessionHandler();
			$session = new Session(handler: $handler);

			$this->expectException(RuntimeException::class);
			$this->expectExceptionMessage('Session handler setup failed');

			$session->start();
		}
	}
}
