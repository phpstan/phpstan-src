<?php

namespace ArrayAppendCount;

use function PHPStan\Testing\assertType;

function multipleConditionalAppends(int $a, int $b, int $c): void
{
	$types = ['base'];
	if ($a > 0) {
		$types[] = 'x';
	} elseif ($a < 0) {
		$types[] = 'y';
	}
	if ($b > 0) {
		$types[] = 'z';
	}
	if ($c === 1) {
		$types[] = 'p';
	} elseif ($c === 2) {
		$types[] = 'q';
	}

	// $types could have 1 (just 'base'), or 2/3/4 depending on which
	// elseif arms fire. count should at least allow 1.
	assertType('int<1, 4>', count($types));

	if (count($types) === 1) {
		// reachable: all three ifs miss — $types stays as ['base'].
		assertType("array{'base'}", $types);
	}
}
