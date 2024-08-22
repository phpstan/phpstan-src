<?php // lint >= 7.2

namespace StrlenIntRange;

use function PHPStan\Testing\assertType;

/**
 * @param int<0, 3> $zeroToThree
 * @param int<2, 3> $twoOrThree
 * @param int<2, max> $twoOrMore
 * @param int<min, 3> $maxThree
 * @param int<10, 11> $tenOrEleven
 */
function doFoo(string $s, $zeroToThree, $twoOrThree, $twoOrMore, int $maxThree, $tenOrEleven): void
{
	if (strlen($s) >= $zeroToThree) {
		assertType('string', $s);
	}
	if (strlen($s) > $zeroToThree) {
		assertType('non-empty-string', $s);
	}

	if (strlen($s) >= $twoOrThree) {
		assertType('non-falsy-string', $s);
	}
	if (strlen($s) > $twoOrThree) {
		assertType('non-falsy-string', $s);
	}

	if (strlen($s) > $twoOrMore) {
		assertType('non-falsy-string', $s);
	}

	$oneOrMore = $twoOrMore-1;
	if (strlen($s) > $oneOrMore) {
		assertType('non-falsy-string', $s);
	}
	if (strlen($s) >= $oneOrMore) {
		assertType('non-empty-string', $s);
	}
	if (strlen($s) <= $oneOrMore) {
		assertType('string', $s);
	} else {
		assertType('non-falsy-string', $s);
	}

	if (strlen($s) > $maxThree) {
		assertType('string', $s);
	}

	if (strlen($s) > $tenOrEleven) {
		assertType('non-falsy-string', $s);
	}
}
