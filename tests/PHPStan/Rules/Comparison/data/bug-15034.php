<?php

declare(strict_types=1);

namespace Bug15034;

$isRunning = false;

$listener = function () use (&$isRunning, &$listener): void {
	if ($isRunning) {
		return;
	}

	$isRunning = true;
	$listener();
	$isRunning = false;
};

$listener();
