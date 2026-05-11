<?php

declare(strict_types=1);

namespace Celemas\Session;

/** @api */
class Flash
{
	public const string STORAGE = 'celemas_flash_messages';

	/** @param non-empty-string $key */
	public function __construct(
		private readonly Session $session,
		private readonly string $key = self::STORAGE,
	) {}

	public function add(
		string $message,
		string $queue = 'default',
	): void {
		$messages = $this->messages();
		$messages[] = [
			'message' => $message,
			'queue' => $queue,
		];

		$this->session->set($this->key, $messages);
	}

	/** @return array<array-key, array{message: string, queue: string}> */
	public function peek(?string $queue = null): array
	{
		$messages = $this->messages();

		if ($queue === null) {
			return $messages;
		}

		$flashes = [];

		foreach ($messages as $message) {
			if ($message['queue'] === $queue) {
				$flashes[] = $message;
			}
		}

		return $flashes;
	}

	/** @return array<array-key, array{message: string, queue: string}> */
	public function pop(?string $queue = null): array
	{
		$flashes = $this->peek($queue);
		$this->clear($queue);

		return $flashes;
	}

	public function clear(?string $queue = null): void
	{
		if ($queue === null) {
			$this->session->set($this->key, []);

			return;
		}

		$messages = $this->messages();

		foreach ($messages as $key => $message) {
			if ($message['queue'] === $queue) {
				unset($messages[$key]);
			}
		}

		$this->session->set($this->key, $messages);
	}

	public function has(?string $queue = null): bool
	{
		$messages = $this->messages();

		if ($queue === null) {
			return count($messages) > 0;
		}

		foreach ($messages as $message) {
			if ($message['queue'] === $queue) {
				return true;
			}
		}

		return false;
	}

	/** @return array<array-key, array{message: string, queue: string}> */
	private function messages(): array
	{
		/** @psalm-suppress MixedAssignment */
		$messages = $this->session->get($this->key, []);

		if (!is_array($messages)) {
			return [];
		}

		$flashes = [];

		foreach ($messages as $key => $message) {
			if (!is_array($message)) {
				continue;
			}

			/** @psalm-suppress MixedAssignment */
			$body = $message['message'] ?? null;
			/** @psalm-suppress MixedAssignment */
			$queue = $message['queue'] ?? null;

			if (is_string($body) && is_string($queue)) {
				$flashes[$key] = [
					'message' => $body,
					'queue' => $queue,
				];
			}
		}

		return $flashes;
	}
}
