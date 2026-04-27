<?php

declare(strict_types=1);

namespace Duon\Session {
	function setcookie(...$args): bool
	{
		return \Duon\Session\Tests\SessionCookieTest::setCookieResult($args);
	}
}

namespace Duon\Session\Tests {
	use Duon\Session\Session;

	final class SessionCookieTest extends TestCase
	{
		private static array $cookies = [];

		public static function setCookieResult(array $args): bool
		{
			self::$cookies[] = $args;

			return true;
		}

		protected function setUp(): void
		{
			parent::setUp();

			self::$cookies = [];
		}

		public function testDestroyPreservesCookieMetadataWhenDeletingCookie(): void
		{
			$session = new Session(options: [
				'cookie_secure' => true,
				'cookie_samesite' => 'Strict',
			]);
			$session->start();

			$session->destroy();

			self::assertCount(1, self::$cookies);
			self::assertSame('PHPSESSID', self::$cookies[0][0]);
			self::assertSame('', self::$cookies[0][1]);
			self::assertIsArray(self::$cookies[0][2]);
			self::assertLessThan(time(), self::$cookies[0][2]['expires']);
			self::assertSame('/', self::$cookies[0][2]['path']);
			self::assertArrayNotHasKey('domain', self::$cookies[0][2]);
			self::assertTrue(self::$cookies[0][2]['secure']);
			self::assertTrue(self::$cookies[0][2]['httponly']);
			self::assertSame('Strict', self::$cookies[0][2]['samesite']);
			self::assertFalse(self::$cookies[0][2]['partitioned']);
		}

		public function testDestroyPreservesCookieDomainWhenDeletingCookie(): void
		{
			$session = new Session(options: ['cookie_domain' => 'example.com']);
			$session->start();

			$session->destroy();

			self::assertCount(1, self::$cookies);
			self::assertIsArray(self::$cookies[0][2]);
			self::assertSame('example.com', self::$cookies[0][2]['domain']);
		}
	}
}
