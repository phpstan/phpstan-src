<?php // lint >= 7.2

namespace StrlenIntRange;

use function PHPStan\Testing\assertType;

/**
 * @param int<0, 3> $zeroToThree
 * @param int<2, 3> $twoOrThree
 * @param int<2, max> $twoOrMore
 * @param int<min, 3> $maxThree
 * @param 10|11 $tenOrEleven
 * @param 0|11 $zeroOrEleven
 * @param int<-10,-5> $negative
 */
function doFoo(string $s, $zeroToThree, $twoOrThree, $twoOrMore, int $maxThree, $tenOrEleven, $zeroOrEleven, int $negative): void
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

	if (strlen($s) == $zeroToThree) {
		assertType('string', $s);
	}
	if (strlen($s) === $zeroToThree) {
		assertType('string', $s);
	}

	if (strlen($s) == $twoOrThree) {
		assertType('non-falsy-string', $s);
	}
	if (strlen($s) === $twoOrThree) {
		assertType('non-falsy-string', $s);
	}

	if (strlen($s) == $oneOrMore) {
		assertType('non-empty-string', $s);
	}
	if (strlen($s) === $oneOrMore) {
		assertType('non-empty-string', $s);
	}

	if (strlen($s) == $tenOrEleven) {
		assertType('non-falsy-string', $s);
	}
	if (strlen($s) === $tenOrEleven) {
		assertType('non-falsy-string', $s);
	}
	if ($tenOrEleven == strlen($s)) {
		assertType('non-falsy-string', $s);
	}
	if ($tenOrEleven === strlen($s)) {
		assertType('non-falsy-string', $s);
	}

	if (strlen($s) == $maxThree) {
		assertType('string', $s);
	}
	if (strlen($s) === $maxThree) {
		assertType('string', $s);
	}

	if (strlen($s) == $zeroOrEleven) {
		assertType('string', $s);
	}
	if (strlen($s) === $zeroOrEleven) {
		assertType('string', $s);
	}

	if (strlen($s) == $negative) {
		assertType('*NEVER*', $s);
	} else {
		assertType('string', $s);
	}
	if (strlen($s) === $negative) {
		assertType('*NEVER*', $s);
	} else {
		assertType('string', $s);
	}
}

/**
 * @param int<1, max> $oneOrMore
 * @param int<2, max> $twoOrMore
 */
function doFooBar(string $s, array $arr, int $oneOrMore, int $twoOrMore): void
{
	if (count($arr) == $oneOrMore) {
		assertType('non-empty-array', $arr);
	}
	if (count($arr) === $twoOrMore) {
		assertType('non-empty-array', $arr);
	}

	if (strlen($s) == $oneOrMore) {
		assertType('non-empty-string', $s);
	}
	if (strlen($s) === $oneOrMore) {
		assertType('non-empty-string', $s);
	}

	if (strlen($s) == $twoOrMore) {
		assertType('non-falsy-string', $s);
	}
	if (strlen($s) === $twoOrMore) {
		assertType('non-falsy-string', $s);
	}

}
