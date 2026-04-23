<?php

declare(strict_types = 1);

namespace Bug14314;

use function PHPStan\Testing\assertType;

function () {
	preg_match('/^(.)$/', '', $matches) || preg_match('/^(.)(.)(.)$/', '', $matches);
	assertType('array{}|array{non-falsy-string, non-empty-string, non-empty-string, non-empty-string}|array{non-falsy-string, non-empty-string}', $matches);
	if (count($matches) === 2) {
		assertType('array{non-falsy-string, non-empty-string}', $matches);
		return;
	}
	assertType('array{}|array{non-falsy-string, non-empty-string, non-empty-string, non-empty-string}', $matches);
	if (count($matches) === 4) {
		assertType('array{non-falsy-string, non-empty-string, non-empty-string, non-empty-string}', $matches);
	}
};

class Bug14314Test
{
	/**
	 * Test: union with array{} and arrays with optional keys
	 * @param array{}|array{0: string, 1: string, 2?: string} $arr
	 */
	public function testOptionalKeysWithEmpty(array $arr): void
	{
		assertType('array{}|array{0: string, 1: string, 2?: string}', $arr);
		if (count($arr) === 3) {
			assertType('array{string, string, string}', $arr);
			return;
		}
		// Fallback keeps full array{0: string, 1: string, 2?: string} since its size
		// range (2..3) is not fully contained in sizeType (3)
		assertType('array{}|array{0: string, 1: string, 2?: string}', $arr);
	}

	/**
	 * Test: IntegerRange sizeType fully covers optional key size range - array is correctly removed
	 * @param array{}|array{0: string, 1: string, 2?: string} $arr
	 * @param int<2, 3> $twoOrThree
	 */
	public function testIntRangeFullyCoveringOptionalKeys(array $arr, int $twoOrThree): void
	{
		if (count($arr) === $twoOrThree) {
			assertType('array{0: string, 1: string, 2?: string}', $arr);
			return;
		}
		// int<2,3> fully covers the optional-key array's size range (2..3),
		// so the array is correctly removed in the falsey branch
		assertType('array{}', $arr);
	}

	/**
	 * Test: IntegerRange partially covers optional key size range - array is kept
	 * @param array{}|array{0: string, 1: string, 2?: string, 3?: string} $arr
	 * @param int<2, 3> $twoOrThree
	 */
	public function testIntRangePartiallyCoveringOptionalKeys(array $arr, int $twoOrThree): void
	{
		if (count($arr) === $twoOrThree) {
			assertType('array{0: string, 1: string, 2?: string, 3?: string}', $arr);
			return;
		}
		// int<2,3> does NOT fully cover size range (2..4), so the array is kept
		assertType('array{}|array{0: string, 1: string, 2?: string, 3?: string}', $arr);
	}

	/**
	 * Test: IntegerRange sizeType with union of constant arrays including array{}
	 * @param array{}|array{string}|array{string, string, string, string} $arr
	 * @param int<2, 4> $twoToFour
	 */
	public function testIntRangeWithUnionAndEmpty(array $arr, int $twoToFour): void
	{
		if (count($arr) === $twoToFour) {
			assertType('array{string, string, string, string}', $arr);
			return;
		}
		assertType('array{}|array{string}', $arr);
	}
}

// Test: sequential count checks preserve narrowing correctly
function () {
	preg_match('/^(.)$/', '', $m) || preg_match('/^(.)(.)(.)$/', '', $m) || preg_match('/^(.)(.)(.)(.)(.)(.)$/', '', $m);
	assertType('array{}|array{non-falsy-string, non-empty-string, non-empty-string, non-empty-string, non-empty-string, non-empty-string, non-empty-string}|array{non-falsy-string, non-empty-string, non-empty-string, non-empty-string}|array{non-falsy-string, non-empty-string}', $m);
	if (count($m) === 2) {
		assertType('array{non-falsy-string, non-empty-string}', $m);
		return;
	}
	assertType('array{}|array{non-falsy-string, non-empty-string, non-empty-string, non-empty-string, non-empty-string, non-empty-string, non-empty-string}|array{non-falsy-string, non-empty-string, non-empty-string, non-empty-string}', $m);
	if (count($m) === 4) {
		assertType('array{non-falsy-string, non-empty-string, non-empty-string, non-empty-string}', $m);
		return;
	}
	assertType('array{}|array{non-falsy-string, non-empty-string, non-empty-string, non-empty-string, non-empty-string, non-empty-string, non-empty-string}', $m);
	if (count($m) === 7) {
		assertType('array{non-falsy-string, non-empty-string, non-empty-string, non-empty-string, non-empty-string, non-empty-string, non-empty-string}', $m);
	}
};

// Test: count narrowing does not lose other variable types
function (int $x) {
	preg_match('/^(.)$/', '', $matches) || preg_match('/^(.)(.)(.)$/', '', $matches);
	if ($x > 0) {
		assertType('int<1, max>', $x);
		if (count($matches) === 2) {
			assertType('array{non-falsy-string, non-empty-string}', $matches);
			assertType('int<1, max>', $x);
			return;
		}
		assertType('array{}|array{non-falsy-string, non-empty-string, non-empty-string, non-empty-string}', $matches);
		assertType('int<1, max>', $x);
	}
};
