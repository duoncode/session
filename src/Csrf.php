<?php

declare(strict_types=1);

namespace Duon\Session;

/** @api */
class Csrf
{
	/**
	 * @param non-empty-string $key
	 * @param non-empty-string $field
	 * @param non-empty-string $header
	 */
	public function __construct(
		private readonly Session $session,
		private readonly string $key = 'duon_csrf_tokens',
		private readonly string $field = '_token',
		private readonly string $header = 'X-CSRF-Token',
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

	public function refresh(string $page = 'default'): string
	{
		return $this->set($page);
	}

	public function remove(string $page = 'default'): void
	{
		$tokens = $this->tokens();
		unset($tokens[$page]);
		$this->session->set($this->key, $tokens);
	}

	public function verify(
		string $page = 'default',
		#[\SensitiveParameter]
		?string $token = null,
	): bool {
		$token ??= $_POST[$this->field] ?? $_SERVER[$this->serverHeader()] ?? null;

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
		$this->session->set($this->key, $tokens);

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
		if (!$this->session->has($this->key)) {
			$this->session->set($this->key, []);
		}
	}

	/** @return array<array-key, string> */
	private function tokens(): array
	{
		/** @psalm-suppress MixedAssignment */
		$tokens = $this->session->get($this->key, []);

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

	/** @return non-empty-string */
	private function serverHeader(): string
	{
		$header = strtoupper(strtr($this->header, '-', '_'));

		if (str_starts_with($header, 'HTTP_')) {
			return $header;
		}

		return 'HTTP_' . $header;
	}
}
