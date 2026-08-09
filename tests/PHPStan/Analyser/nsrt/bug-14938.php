<?php

namespace Bug14938;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array{a?: string} $optStr
	 * @param array{0: int, a?: string} $listPlusOptStr
	 * @param array{-1?: string} $optNeg
	 * @param array{0: int, 5?: string} $listPlusGap
	 * @param array{a: string} $reqStr
	 * @param array{1: string} $gapReq
	 * @param array{0?: string} $optZero
	 */
	public function doFoo(
		array $optStr,
		array $listPlusOptStr,
		array $optNeg,
		array $listPlusGap,
		array $reqStr,
		array $gapReq,
		array $optZero,
	): void
	{
		// An optional non-list key might be absent, so the array can still be
		// a list ([] / the list prefix) — array_is_list() is not decidable.
		assertType('bool', array_is_list($optStr));
		assertType('bool', array_is_list($listPlusOptStr));
		assertType('bool', array_is_list($optNeg));
		assertType('bool', array_is_list($listPlusGap));

		// A required non-list key is always present, so it is never a list.
		assertType('false', array_is_list($reqStr));
		assertType('false', array_is_list($gapReq));

		// Only an optional key 0 keeps it a guaranteed list ([] and [v]).
		assertType('true', array_is_list($optZero));
	}

	/**
	 * @param array{a?: string} $optStr
	 */
	public function narrowing(array $optStr): void
	{
		if (array_is_list($optStr)) {
			assertType('list{}&list', $optStr);
		} else {
			// array_is_list()'s false branch is not narrowed, so `a` stays optional
			// here rather than being refined to array{a: string}.
			assertType('array{a?: string}', $optStr);
		}
	}

	public function mergedShapes(): void
	{
		// Merging a list with a shape that adds a string key: the empty-of-the-extra
		// realization is still a list, so array_is_list() is not decidable.
		$a = [0 => 'z'];
		if (rand(0, 1)) {
			$a['y'] = 1;
		}
		assertType("array{0: 'z', y?: 1}", $a);
		assertType('bool', array_is_list($a));

		// Two pure lists merge into a list (optional keys stay a suffix).
		$b = [0 => 'z'];
		if (rand(0, 1)) {
			$b[1] = 'w';
		}
		assertType("array{0: 'z', 1?: 'w'}", $b);
		assertType('true', array_is_list($b));

		// Shapes disjoint except for the empty array still admit the empty list.
		$c = [];
		if (rand(0, 1)) {
			$c['a'] = 1;
		}
		assertType('array{}|array{a: 1}', $c);
		assertType('bool', array_is_list($c));
	}

}
