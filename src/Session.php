<?php

declare(strict_types=1);

namespace Duon\Session;

use SessionHandlerInterface;

/** @psalm-api */
class Session
{
	public const string REMEMBER = 'duon_remembered_uri';

	private const array DEFAULT_OPTIONS = [
		'cache_limiter' => 'nocache',
		'cookie_httponly' => true,
		'cookie_samesite' => 'Lax',
		'use_only_cookies' => true,
		'use_strict_mode' => true,
		'use_trans_sid' => false,
	];

	protected readonly array $options;

	/** @psalm-suppress UnusedProperty Used by the $flash property hook. */
	private ?Flash $flashInstance = null;

	/** @psalm-suppress UnusedProperty Used by the $csrf property hook. */
	private ?Csrf $csrfInstance = null;

	/** @psalm-suppress PropertyNotSetInConstructor Virtual property backed by a get hook. */
	public Flash $flash {
		get => $this->flashInstance ??= new Flash($this);
	}

	/** @psalm-suppress PropertyNotSetInConstructor Virtual property backed by a get hook. */
	public Csrf $csrf {
		get => $this->csrfInstance ??= new Csrf($this);
	}

	public function __construct(
		protected readonly string $name = '',
		array $options = [],
		protected readonly ?SessionHandlerInterface $handler = null,
	) {
		$this->options = array_replace(self::DEFAULT_OPTIONS, $options);
	}

	public function start(): void
	{
		if (session_status() !== PHP_SESSION_NONE) {
			return;
		}

		if (headers_sent($file, $line)) {
			// Requires sent headers, which the test suite cannot trigger reliably.
			// @codeCoverageIgnoreStart
			throw new RuntimeException(
				__METHOD__ . 'Session started after headers sent. File: ' . $file . ' line: ' . $line,
			);

			// @codeCoverageIgnoreEnd
		}

		if ($this->name) {
			session_name($this->name);
		}

		if ($this->handler && !session_set_save_handler($this->handler, true)) {
			throw new RuntimeException('Session handler setup failed');
		}

		if (!session_start($this->options)) {
			// session_start() only returns false after PHP accepts the setup but fails internally;
			// forcing that path would make the test process depend on unstable runtime state.
			throw new RuntimeException(__METHOD__ . 'session_start failed.'); // @codeCoverageIgnore
		}
	}

	public function destroy(): void
	{
		$this->assertActive();

		session_unset();

		$useCookies = ini_get('session.use_cookies');
		if ($useCookies === '1') {
			$params = session_get_cookie_params();
			$name = session_name();
			if ($name !== false) {
				/** @psalm-suppress InvalidArrayOffset PHP 8.5 exposes partitioned cookies. */
				$partitioned = (bool) ($params['partitioned'] ?? false);
				$options = [
					'expires' => time() - 42000,
					'path' => (string) $params['path'],
					'secure' => (bool) $params['secure'],
					'httponly' => (bool) $params['httponly'],
					'samesite' => (string) $params['samesite'],
					'partitioned' => $partitioned,
				];

				$domain = (string) $params['domain'];
				if ($domain !== '') {
					$options['domain'] = $domain;
				}

				setcookie($name, '', $options);
			}
		}

		if (!session_destroy()) {
			throw new RuntimeException('Session destroy failed');
		}
	}

	public function name(): string
	{
		$name = session_name();
		if ($name === false) {
			throw new RuntimeException('Session name not available');
		}

		return $name;
	}

	public function id(): string
	{
		$id = session_id();
		if ($id === false) {
			throw new RuntimeException('Session id not available');
		}

		return $id;
	}

	/** @return array<array-key, mixed> */
	public function all(): array
	{
		$this->assertActive();

		/** @var array<array-key, mixed> $session */
		$session = $_SESSION;

		return $session;
	}

	public function clear(): void
	{
		$this->assertActive();

		session_unset();
	}

	/** @psalm-param non-empty-string $key */
	public function get(string $key, mixed $default = null): mixed
	{
		$this->assertActive();

		if ($this->has($key)) {
			return $_SESSION[$key];
		}

		if (func_num_args() > 1) {
			return $default;
		}

		throw new OutOfBoundsException(
			"The session key '{$key}' does not exist",
		);
	}

	/**
	 * @psalm-suppress MixedAssignment
	 * @psalm-param non-empty-string $key
	 */
	public function pull(string $key, mixed $default = null): mixed
	{
		$this->assertActive();

		if ($this->has($key)) {
			$value = $_SESSION[$key];
			unset($_SESSION[$key]);

			return $value;
		}

		if (func_num_args() > 1) {
			return $default;
		}

		throw new OutOfBoundsException(
			"The session key '{$key}' does not exist",
		);
	}

	/**
	 * @psalm-suppress MixedAssignment
	 * @psalm-param non-empty-string $key
	 * */
	public function set(string $key, mixed $value): void
	{
		$this->assertActive();

		$_SESSION[$key] = $value;
	}

	/** @psalm-param non-empty-string $key */
	public function has(string $key): bool
	{
		$this->assertActive();

		return ($_SESSION[$key] ?? null) !== null;
	}

	/** @psalm-param non-empty-string $key */
	public function remove(string $key): void
	{
		$this->assertActive();

		unset($_SESSION[$key]);
	}

	public function active(): bool
	{
		return session_status() === PHP_SESSION_ACTIVE;
	}

	public function close(): void
	{
		$this->assertActive();

		if (!session_write_close()) {
			throw new RuntimeException('Session close failed');
		}
	}

	public function regenerate(): void
	{
		$this->assertActive();

		if (!session_regenerate_id(true)) {
			throw new RuntimeException('Session id regeneration failed');
		}
	}

	public function rememberUri(
		string $uri,
		int $expires = 3600,
	): void {
		$this->assertActive();

		$rememberedUri = [
			'uri' => $uri,
			'expires' => time() + $expires,
		];
		$_SESSION[self::REMEMBER] = $rememberedUri;
	}

	public function rememberedUri(): string
	{
		$this->assertActive();

		/** @var null|array{uri: string, expires: int} */
		$rememberedUri = $_SESSION[self::REMEMBER] ?? null;

		if ($rememberedUri) {
			if ($rememberedUri['expires'] > time()) {
				$uri = $rememberedUri['uri'];
				unset($_SESSION[self::REMEMBER]);

				if (self::isLocalUri($uri)) {
					return $uri;
				}
			}

			unset($_SESSION[self::REMEMBER]);
		}

		return '/';
	}

	private function assertActive(): void
	{
		if (!$this->active()) {
			throw new RuntimeException('Session not started');
		}
	}

	private static function isLocalUri(string $uri): bool
	{
		if ($uri === '') {
			return false;
		}

		if (!str_starts_with($uri, '/') || str_starts_with($uri, '//')) {
			return false;
		}

		$decodedUri = rawurldecode($uri);

		if (preg_match('/[\x00-\x1F\x7F]/', $decodedUri) === 1) {
			return false;
		}

		if (str_contains($decodedUri, '\\')) {
			return false;
		}

		if (!str_starts_with($decodedUri, '/') || str_starts_with($decodedUri, '//')) {
			return false;
		}

		$parts = parse_url($uri);

		return (
			is_array($parts)
			&& !array_key_exists('scheme', $parts)
			&& !array_key_exists('host', $parts)
			&& array_key_exists('path', $parts)
		);
	}
}
