<?php declare(strict_types=1);

namespace NarrowTaggedUnion;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/** @param array{string, '', non-empty-string}|array{string, numeric-string} $arr */
	public function sayHello(array $arr): void
	{
		if (count($arr) === 0) {
			assertType('*NEVER*', $arr);
		} else {
			assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);
		}
		assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);

		if (count($arr) === 1) {
			assertType('*NEVER*', $arr);
		} else {
			assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);
		}
		assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);

		if (count($arr) === 2) {
			assertType('array{string, numeric-string}', $arr);
		} else {
			assertType("array{string, '', non-empty-string}", $arr);
		}
		assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);

		if (count($arr) === 3) {
			assertType("array{string, '', non-empty-string}", $arr);
		} else {
			assertType('array{string, numeric-string}', $arr);
		}
		assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);

		if (count($arr, COUNT_NORMAL) === 3) {
			assertType("array{string, '', non-empty-string}", $arr);
		} else {
			assertType('array{string, numeric-string}', $arr);
		}
		assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);

		if (count($arr, COUNT_RECURSIVE) === 3) {
			assertType("array{string, '', non-empty-string}", $arr);
		} else {
			assertType('array{string, numeric-string}', $arr);
		}
		assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);

		if (count($arr) === 4) {
			assertType('*NEVER*', $arr);
		} else {
			assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);
		}
		assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);

	}

	/** @param array{string, '', non-empty-string}|array{array<int>, numeric-string} $arr */
	public function nestedArrays(array $arr): void
	{
		// don't narrow when $arr contains recursive arrays
		if (count($arr, COUNT_RECURSIVE) === 3) {
			assertType("array{array<int>, numeric-string}|array{string, '', non-empty-string}", $arr);
		} else {
			assertType("array{array<int>, numeric-string}|array{string, '', non-empty-string}", $arr);
		}
		assertType("array{array<int>, numeric-string}|array{string, '', non-empty-string}", $arr);

		if (count($arr, COUNT_NORMAL) === 3) {
			assertType("array{string, '', non-empty-string}", $arr);
		} else {
			assertType("array{array<int>, numeric-string}", $arr);
		}
		assertType("array{array<int>, numeric-string}|array{string, '', non-empty-string}", $arr);
	}

	public function arrayIntRangeSize(): void
	{
		$x = [];
		if (rand(0,1)) {
			$x[] = 'ab';
		}
		if (rand(0,1)) {
			$x[] = 'xy';
		}

		if (count($x) === 1) {
			assertType("array{'xy'}|array{0: 'ab', 1?: 'xy'}", $x);
		} else {
			assertType("array{}|array{'xy'}|array{0: 'ab', 1?: 'xy'}", $x);
		}
		assertType("array{}|array{'xy'}|array{0: 'ab', 1?: 'xy'}", $x);
	}
}

