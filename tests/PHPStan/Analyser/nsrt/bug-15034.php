<?php

declare(strict_types=1);

namespace Bug15034;

use function PHPStan\Testing\assertType;

$isRunning = false;

$listener = function () use (&$isRunning, &$listener): void {
	assertType('bool', $isRunning);
	if ($isRunning) {
		return;
	}

	$isRunning = true;
	$listener();
	$isRunning = false;
};

$listener();
