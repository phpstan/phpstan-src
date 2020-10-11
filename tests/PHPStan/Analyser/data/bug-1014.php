<?php declare(strict_types=1);

use function PHPStan\Analyser\assertType;

function bug1014(): void {
	$s = rand(0, 1) ? 0 : 1;
	assertType('0|1', $s);
	if ($s) {
		assertType('1', $s);
		$s = 3;
		assertType('3', $s);
	} else {
		assertType('0', $s);
	}
	assertType('0|3', $s);
	if ($s === 1) {
	}
}
