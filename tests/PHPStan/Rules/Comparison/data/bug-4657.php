<?php

namespace Bug4657;

use DateTime;

function (): void {
	$value = null;
	$callback = function () use (&$value) : void {
		$value = new DateTime();
	};
	$callback();

	assert(!is_null($value));
};
