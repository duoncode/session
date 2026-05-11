<?php

declare(strict_types=1);

namespace Celemas\Session;

/** @api */
final class Helpers implements Contract\Helpers
{
	#[\Override]
	public function flash(Session $session): Flash
	{
		return new Flash($session);
	}

	#[\Override]
	public function csrf(Session $session): Csrf
	{
		return new Csrf($session);
	}

	#[\Override]
	public function uri(Session $session): Uri
	{
		return new Uri($session);
	}
}
