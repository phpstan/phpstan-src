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
function doOffsetCapture(string $s): void {
	if (preg_match('/(foo)(bar)(baz)/', $s, $matches, PREG_OFFSET_CAPTURE)) {
		assertType('array{array{string, int<0, max>}, array{string, int<0, max>}, array{string, int<0, max>}, array{string, int<0, max>}}', $matches);
	}
	assertType('array<array{string, int<-1, max>}>', $matches);
}

