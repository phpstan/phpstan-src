<?php

namespace Bug4657;

use DateTime;

function (): void {
	$value = null;
	$callback = function () use (&$value) : void {
		$value = new DateTime();
	};
	$callback();

	// phpstan: Call to static method Webmozart\Assert\Assert::notNull() with DateTime|null will always evaluate to false.
	assert(!is_null($value));
};
