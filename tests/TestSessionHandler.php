<?php

declare(strict_types=1);

namespace Celemas\Session\Tests;

use SessionHandlerInterface;

class TestSessionHandler implements SessionHandlerInterface
{
	public array $sessions;
	public bool $visited = false;

	public function open(string $savePath, string $sessionName): bool
	{
		$this->sessions = [];
		$this->visited = true;

		return true;
	}

	public function close(): bool
	{
		return true;
	}

	public function read(string $id): string|false
	{
		return $this->sessions[$id] ?? '';
	}

	public function write(string $id, string $data): bool
	{
		$this->sessions[$id] = $data;

		return true;
	}

	public function destroy($id): bool
	{
		unset($this->sessions);

		return true;
	}

	public function gc($maxlifetime): int|false
	{
		return 1;
	}
}
