<?php // lint >= 8.2

namespace PregMatchShapesPhp82;

use function PHPStan\Testing\assertType;

// n modifier captures only named groups
// https://php.watch/versions/8.2/preg-n-no-capture-modifier
function doNonAutoCapturingFlag(string $s): void {
	if (preg_match('/(\d+)/n', $s, $matches)) {
		assertType('array{string, string}', $matches);
	}
	assertType('array{}|array{string, string}', $matches);

	if (preg_match('/(\d+)(?P<num>\d+)/n', $s, $matches)) {
		// could be assertType('array{0: string, num: string, 1: string}', $matches);
		assertType('array<string>', $matches);
	}
	assertType('array<string>', $matches);
}
