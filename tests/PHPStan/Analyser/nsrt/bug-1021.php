<?php declare(strict_types=1);

namespace Bug1021;

use function PHPStan\Testing\assertType;

function foobar() {
	$x = [1, 2, 3];

	foreach ([4, 5, 6] as $i) {
		if (rand(0, 1)) {
			array_shift($x);
		}
	}

	assertType('array{}|array{1, 2, 3}|array{2, 3}|array{3}', $x);

	if ($x) {
	}
}

function foo(array $x) {
	if ($x) {
		array_shift($x);

		assertType('array', $x);

		if ($x) {
			echo "";
		}
	}
}

/**
 * @param list<int> $ints
 */
function foobar2(array $ints) {
	$x = [1, 2, 3];

	foreach ($ints as $i) {
		if (rand(0, 1)) {
			array_shift($x);
		}
	}

	assertType('list<1|2|3>', $x);

	if ($x) {
	}
}
