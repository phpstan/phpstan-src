<?php declare(strict_types = 1);

namespace Bug7913b;

use function PHPStan\Testing\assertType;

const X = [];
assertType('array{}', X);
if (!empty(X)) {
	assertType('*NEVER*', X);
	foreach (X as $y) {
		print($y);
	}
}
assertType('array{}', X);

$x = [];
if (!empty($x)) {
	foreach ($x as $y) {
		print($y);
	}
}
