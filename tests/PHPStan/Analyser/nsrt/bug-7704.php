<?php declare(strict_types = 1);

namespace Bug7704;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @template TRest
 *
 * @param T $first
 * @param TRest ...$rest
 *
 * @return T|TRest
 */
function intersection($first, ...$rest)
{
	$ret = $first;
	foreach ($rest as $item) {
		if (rand(0, 1)) {
			$ret = $item;
		}
	}
	return $ret;
}

assertType("'a'", intersection("a"));
assertType("'a'|'b'|'c'", intersection("a", "b", "c"));
assertType('1', intersection(1));
assertType('1|2|3', intersection(1, 2, 3));
