<?php

namespace Bug162;

use function PHPStan\Testing\assertType;
use const PHP_VERSION;
use const PHP_VERSION_ID;

function lower(): void
{
	// add a upper bound, so we don't need to adjust
	// the test when PHPStan adds support for PHP8.6+
	if (PHP_VERSION_ID > 80599) {
		return;
	}

	// lower limit inferred from composer.json
	$x = PHP_VERSION_ID;
	assertType('int<80000, 80599>', $x);

	if (
		version_compare( PHP_VERSION, '8.4', '<' )
	) {
		$x = PHP_VERSION_ID;
		assertType('int<80000, 80399>', $x);
	}
}
