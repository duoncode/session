<?php

declare(strict_types=1);

namespace Duon\Session;

/** @psalm-api */
class Csrf
{
	/**
	 * @param non-empty-string $sessionKey
	 * @param non-empty-string $postKey
	 * @param non-empty-string $headerKey
	 */
	public function __construct(
		protected string $sessionKey = 'csrftokens',
		protected string $postKey = 'csrftoken',
		protected string $headerKey = 'HTTP_X_CSRF_TOKEN',
	) {
		if (($_SESSION[$this->sessionKey] ?? null) === null) {
			$_SESSION[$this->sessionKey] = [];
		}
	}

	public function get(string $page = 'default'): string
	{
		return (string) ($_SESSION[$this->sessionKey][$page] ?? $this->set($page));
	}

	public function verify(
		string $page = 'default',
		#[\SensitiveParameter]
		?string $token = null,
	): bool {
		$token ??= $_POST[$this->postKey] ?? $_SERVER[$this->headerKey] ?? null;

		if (!is_string($token)) {
			return false;
		}

		if (hash_equals('', $token) || hash_equals('0', $token)) {
			return false;
		}

		$savedToken = $this->get($page);

		if (hash_equals('', $savedToken) || hash_equals('0', $savedToken)) {
			return false;
		}

		return hash_equals($savedToken, $token);
	}

	protected function set(string $page = 'default'): string
	{
		assert(
			array_key_exists($this->sessionKey, $_SESSION ?? []) && is_array($_SESSION[$this->sessionKey]),
			'CSRF token storage must be an array.',
		);

		$token = base64_encode(random_bytes(32));
		$_SESSION[$this->sessionKey][$page] = $token;

		return $token;
	}
}
