<?php

function () {
	/** @var int|string $s */
	$s = doFoo();
	if (!is_numeric($s)) {
		\PHPStan\Analyser\assertType('string', $s);
	}
};
