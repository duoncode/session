<?php

declare(strict_types=1);

namespace Celemas\Session\Contract;

use Celemas\Session\Csrf;
use Celemas\Session\Flash;
use Celemas\Session\Session;
use Celemas\Session\Uri;

/** @api */
interface Helpers
{
	public function flash(Session $session): Flash;

	public function csrf(Session $session): Csrf;

	public function uri(Session $session): Uri;
}
