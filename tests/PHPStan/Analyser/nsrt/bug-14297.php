<?php declare(strict_types = 1);

namespace Bug14297;

use function PHPStan\Testing\assertType;

function (): void {
	$a = [rand(0, 1) ? 'a' : null];
	if (rand(0, 1)) {
		$a[] = rand(0, 1) ? 'b' : null;
	}

	$a = array_values(array_filter($a));
	if (count($a) === 0) {
		return;
	}

	assertType("non-empty-list{0?: 'a'|'b', 1?: 'b'}", $a);
	assertType("int<1, 2>", count($a));

	if (count($a) === 2) {
		assertType("array{'a'|'b', 'b'}", $a);
	} else {
		assertType("array{'a'|'b'}", $a);
	}
};
