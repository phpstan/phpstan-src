<?php declare(strict_types = 1);

namespace Bug2572Nsrt;

use function PHPStan\Testing\assertType;

/**
 * @template TE
 * @template TR
 *
 * @param TE $elt
 * @param TR ...$elts
 *
 * @return TE|TR
 */
function collect($elt, ...$elts) {
	$ret = $elt;
	foreach ($elts as $item) {
		if (rand(0, 1)) {
			$ret = $item;
		}
	}
	return $ret;
}

assertType("'a'", collect("a"));
assertType("'a'|'b'|'c'", collect("a", "b", "c"));
