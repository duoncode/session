<?php

declare(strict_types=1);

use FiveOrbs\Session\Session;
use FiveOrbs\Session\Tests\TestCase;
use FiveOrbs\Session\Tests\TestSessionHandler;

uses(TestCase::class);

test('Custom handler', function () {
	$handler = new TestSessionHandler();
	$session = new Session('custom', handler: $handler);
	$session->start();
	$session->set('test', 'value');

	expect($session->name())->toBe('custom');
	expect($session->get('test'))->toBe('value');
	expect($handler->visited)->toBe(true);

	$session->forget();
});
