<?php

namespace PregMatchShapesPhp82;

use function PHPStan\Testing\assertType;

function doOnlyNamedSubpattern(string $s): void {
	// n modifier captures only named groups
	if (preg_match('/(\w)-(?P<num>\d+)-(\w)/n', $s, $matches)) {
		assertType('array{0: string, num?: string, 1?: string}', $matches);
	}
	assertType('array<string>', $matches);
}
