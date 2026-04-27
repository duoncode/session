<?php

declare(strict_types=1);

namespace Duon\Session;

/** @psalm-api */
class Uri
{
	public const string REMEMBERED = 'duon_remembered_uri';

	/** @param non-empty-string $key */
	public function __construct(
		private readonly Session $session,
		private readonly string $key = self::REMEMBERED,
	) {}

	public function remember(
		string $uri,
		int $expires = 3600,
	): void {
		$this->session->set($this->key, [
			'uri' => $uri,
			'expires' => time() + $expires,
		]);
	}

	public function pull(string $default = '/'): string
	{
		/** @var mixed $rememberedUri */
		$rememberedUri = $this->session->pull($this->key, null);

		if (!is_array($rememberedUri)) {
			return $default;
		}

		$uri = $rememberedUri['uri'] ?? null;
		$expires = $rememberedUri['expires'] ?? null;

		if (!is_string($uri) || !is_int($expires)) {
			return $default;
		}

		if ($expires <= time()) {
			return $default;
		}

		if (!self::isLocal($uri)) {
			return $default;
		}

		return $uri;
	}

	private static function isLocal(string $uri): bool
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
