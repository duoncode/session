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
		private readonly Session $session,
		private readonly string $sessionKey = 'csrftokens',
		private readonly string $postKey = 'csrftoken',
		private readonly string $headerKey = 'HTTP_X_CSRF_TOKEN',
	) {
		$this->initStorage();
	}

	public function token(string $page = 'default'): string
	{
		$tokens = $this->tokens();
		$token = $tokens[$page] ?? null;

		if (is_string($token)) {
			return $token;
		}

		return $this->set($page);
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

		$savedToken = $this->savedToken($page);

		if ($savedToken === null) {
			return false;
		}

		return hash_equals($savedToken, $token);
	}

	protected function set(string $page = 'default'): string
	{
		$tokens = $this->tokens();
		$token = base64_encode(random_bytes(32));
		$tokens[$page] = $token;
		$this->session->set($this->sessionKey, $tokens);

		return $token;
	}

	private function savedToken(string $page): ?string
	{
		$tokens = $this->tokens();
		$token = $tokens[$page] ?? null;

		return is_string($token) ? $token : null;
	}

	private function initStorage(): void
	{
		if (!$this->session->has($this->sessionKey)) {
			$this->session->set($this->sessionKey, []);
		}
	}

	/** @return array<array-key, string> */
	private function tokens(): array
	{
		/** @psalm-suppress MixedAssignment */
		$tokens = $this->session->get($this->sessionKey, []);

		if (!is_array($tokens)) {
			return [];
		}

		$valid = [];

		/** @psalm-suppress MixedAssignment */
		foreach ($tokens as $page => $token) {
			if (is_string($token)) {
				$valid[$page] = $token;
			}
		}

		return $valid;
	}
}
