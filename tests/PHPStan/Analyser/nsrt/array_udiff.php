<?php

namespace ArrayUdiff;

use function PHPStan\Testing\assertType;

/**
 * @param array<int> $array1
 * @param array<string> $array2
 * @param array<int, int> $array3
 * @param list<string> $list
 * @param non-empty-array<int> $nonEmptyArray
 */
function test(array $array1, array $array2, array $array3, array $list, array $nonEmptyArray): void
{
	assertType('array<int|string, int>', array_udiff($array1, $array2, static function (mixed $a, mixed $b) {
		assertType('int', $a);
		assertType('string', $b);

		return strcasecmp((string) $a, $b);
	}));

	assertType('array<int|string, string>', array_udiff($array2, $array1, static function (mixed $a, mixed $b) {
		assertType('string', $a);
		assertType('int', $b);

		return strcasecmp($a, (string) $b);
	}));

	assertType('array<int|string, int>', array_udiff($array1, $array3, static function (mixed $a, mixed $b) {
		assertType('int', $a);
		assertType('int', $b);

		return $a <=> $b;
	}));

	assertType('array<int, int>', array_udiff($array3, $array1, static function (mixed $a, mixed $b) {
		assertType('int', $a);
		assertType('int', $b);

		return $a <=> $b;
	}));

	assertType('array<int|string, int>', array_udiff($array1, $list, static function (mixed $a, mixed $b) {
		assertType('int', $a);
		assertType('string', $b);

		return strcasecmp((string) $a, $b);
	}));

	assertType('array<int, string>', array_udiff($list, $array1, static function (mixed $a, mixed $b) {
		assertType('string', $a);
		assertType('int', $b);

		return strcasecmp($a, (string) $b);
	}));

	assertType('array<int|string, int>', array_udiff($nonEmptyArray, $array1, static function (mixed $a, mixed $b) {
		assertType('int', $a);
		assertType('int', $b);

		return $a <=> $b;
	}));

	assertType('array<int|string, int>', array_udiff($array1, $nonEmptyArray, static function (mixed $a, mixed $b) {
		assertType('int', $a);
		assertType('int', $b);

		return $a <=> $b;
	}));
}
