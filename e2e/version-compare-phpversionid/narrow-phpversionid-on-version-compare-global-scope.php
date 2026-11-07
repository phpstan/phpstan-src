<?php

// intentional without namespace

use function PHPStan\Testing\assertType;

function narrowPhpVersionIdViaVersionComapre(): void {
	if (PHP_VERSION_ID < 80000) {
		return;
	}
	$x = PHP_VERSION_ID;
	assertType('int<80000, max>', $x);

	if (
		version_compare( PHP_VERSION, '8.4', '<' )
	) {
		$x = PHP_VERSION_ID;
		assertType('int<80000, 80399>', $x);
	}
}

