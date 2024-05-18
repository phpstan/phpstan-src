<?php

namespace PregMatchShapesPhp82;

use function PHPStan\Testing\assertType;

function doOnlyNamedSubpattern(string $s): void {
	// n modifier captures only named groups
	if (preg_match('/(\w)-(?P<num>\d+)-(\w)/n', $s, $matches)) {
		// could be assertType('array{0: string, num: string, 1: string, 2: string, 3: string}', $matches);
		assertType('array<string>', $matches);
	}
	assertType('array<string>', $matches);
}

// n modifier captures only named groups
// https://php.watch/versions/8.2/preg-n-no-capture-modifier
function doNonAutoCapturingFlag(string $s): void {
	if (preg_match('/(\d+)/n', $s, $matches)) {
		assertType('array{string}', $matches);
	}
	assertType('array<string>', $matches);
}

