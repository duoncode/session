<?php

declare(strict_types=1);

namespace Duon\Session {
	function session_name(?string $name = null): string|false
	{
		if ($name !== null) {
			return \session_name($name);
		}

		return \Duon\Session\Tests\SessionNameIdFailureTest::sessionNameResult();
	}

	function session_id(?string $id = null): string|false
	{
		if ($id !== null) {
			return \session_id($id);
		}

		return \Duon\Session\Tests\SessionNameIdFailureTest::sessionIdResult();
	}

	function session_regenerate_id($deleteOldSession = false): bool
	{
		return \Duon\Session\Tests\SessionNameIdFailureTest::sessionRegenerateIdResult($deleteOldSession);
	}

	function session_write_close(): bool
	{
		return \Duon\Session\Tests\SessionNameIdFailureTest::sessionWriteCloseResult();
	}
}

namespace Duon\Session\Tests {
	use Duon\Session\RuntimeException;
	use Duon\Session\Session;

	final class SessionNameIdFailureTest extends TestCase
	{
		private static bool $forceNameFalse = false;
		private static bool $forceIdFalse = false;
		private static bool $forceRegenerateIdFalse = false;
		private static bool $forceWriteCloseFalse = false;

		public static function sessionNameResult(): string|false
		{
			if (self::$forceNameFalse) {
				return false;
			}

			return \session_name();
		}

		public static function sessionIdResult(): string|false
		{
			if (self::$forceIdFalse) {
				return false;
			}

			return \session_id();
		}

		public static function sessionRegenerateIdResult($deleteOldSession): bool
		{
			if (self::$forceRegenerateIdFalse) {
				return false;
			}

			return \session_regenerate_id((bool) $deleteOldSession);
		}

		public static function sessionWriteCloseResult(): bool
		{
			if (self::$forceWriteCloseFalse) {
				return false;
			}

			return \session_write_close();
		}

		protected function tearDown(): void
		{
			self::$forceNameFalse = false;
			self::$forceIdFalse = false;
			self::$forceRegenerateIdFalse = false;
			self::$forceWriteCloseFalse = false;

			if (session_status() === PHP_SESSION_ACTIVE) {
				session_unset();
				session_destroy();
			}

			parent::tearDown();
		}

		public function testNameThrowsWhenUnavailable(): void
		{
			self::$forceNameFalse = true;
			$session = new Session();

			$this->expectException(RuntimeException::class);
			$this->expectExceptionMessage('Session name not available');

			$session->name();
		}

		public function testIdThrowsWhenUnavailable(): void
		{
			self::$forceIdFalse = true;
			$session = new Session();

			$this->expectException(RuntimeException::class);
			$this->expectExceptionMessage('Session id not available');

			$session->id();
		}

		public function testRegenerateThrowsWhenRegenerationFails(): void
		{
			$session = new Session();
			$session->start();
			self::$forceRegenerateIdFalse = true;

			$this->expectException(RuntimeException::class);
			$this->expectExceptionMessage('Session id regeneration failed');

			$session->regenerate();
		}

		public function testCloseThrowsWhenWriteCloseFails(): void
		{
			$session = new Session();
			$session->start();
			self::$forceWriteCloseFalse = true;

			$this->expectException(RuntimeException::class);
			$this->expectExceptionMessage('Session close failed');

			$session->close();
		}
	}
}
