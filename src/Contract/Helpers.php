<?php

declare(strict_types=1);

namespace Duon\Session\Contract;

use Duon\Session\Csrf;
use Duon\Session\Flash;
use Duon\Session\Session;
use Duon\Session\Uri;

/** @api */
interface Helpers
{
	public function flash(Session $session): Flash;

	public function csrf(Session $session): Csrf;

	public function uri(Session $session): Uri;
}
