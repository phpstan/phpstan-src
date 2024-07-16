<?php // lint >= 8.2

namespace PregMatchShapesPhp82;

use function PHPStan\Testing\assertType;

// n modifier captures only named groups
// https://php.watch/versions/8.2/preg-n-no-capture-modifier
function doNonAutoCapturingFlag(string $s): void {
	if (preg_match('/(\d+)/n', $s, $matches)) {
		assertType('array{string, non-empty-string&numeric-string}', $matches); // should be 'array{string}'
	}
	assertType('array{}|array{string, non-empty-string&numeric-string}', $matches);

	if (preg_match('/(\d+)(?P<num>\d+)/n', $s, $matches)) {
		assertType('array{0: string, 1: non-empty-string&numeric-string, num: non-empty-string&numeric-string, 2: non-empty-string&numeric-string}', $matches);
	}
	assertType('array{}|array{0: string, 1: non-empty-string&numeric-string, num: non-empty-string&numeric-string, 2: non-empty-string&numeric-string}', $matches);
}
