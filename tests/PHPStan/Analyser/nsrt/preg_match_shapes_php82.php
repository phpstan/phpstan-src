<?php // lint >= 8.2

namespace PregMatchShapesPhp82;

use function PHPStan\Testing\assertType;

// n modifier captures only named groups
// https://php.watch/versions/8.2/preg-n-no-capture-modifier
function doNonAutoCapturingFlag(string $s): void {
	if (preg_match('/(\d+)/n', $s, $matches)) {
		assertType('array{string}', $matches);
	}
	assertType('array{}|array{string}', $matches);

	if (preg_match('/(\d+)(?P<num>\d+)/n', $s, $matches)) {
		assertType('array{0: string, num: numeric-string, 1: numeric-string}', $matches);
	}
	assertType('array{}|array{0: string, num: numeric-string, 1: numeric-string}', $matches);

	if (preg_match('/(\w)-(?P<num>\d+)-(\w)/n', $s, $matches)) {
		assertType('array{0: string, num: numeric-string, 1: numeric-string}', $matches);
	}
	assertType('array{}|array{0: string, num: numeric-string, 1: numeric-string}', $matches);
}

// delimiter variants, see https://www.php.net/manual/en/regexp.reference.delimiters.php
function (string $s): void {
	if (preg_match('{(\d+)(?P<num>\d+)}n', $s, $matches)) {
		assertType('array{0: string, num: numeric-string, 1: numeric-string}', $matches);
	}
};
function (string $s): void {
	if (preg_match('<(\d+)(?P<num>\d+)>n', $s, $matches)) {
		assertType('array{0: string, num: numeric-string, 1: numeric-string}', $matches);
	}
};
function (string $s): void {
	if (preg_match('((\d+)(?P<num>\d+))n', $s, $matches)) {
		assertType('array{0: string, num: numeric-string, 1: numeric-string}', $matches);
	}
};
