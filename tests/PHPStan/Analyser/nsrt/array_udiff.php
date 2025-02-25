<?php

namespace ArrayUdiff;

use function PHPStan\Testing\assertType;

/**
 * @param array<int> $array1
 * @param array<string> $array2
 * @param array<int, int> $array3
 * @param list<string> $list
 * @param non-empty-array<int> $nonEmptyArray
 * @param array{foo: string, 0?: int} $arrayShape1
 * @param array{foo: string, bar?: int} $arrayShape2
 */
function test(array $array1, array $array2, array $array3, array $list, array $nonEmptyArray, array $arrayShape1, array $arrayShape2): void
{
	assertType('array<int>', array_udiff($array1, $array2, static function (mixed $a, mixed $b) {
		assertType('int', $a);
		assertType('string', $b);

		return strcasecmp((string) $a, $b);
	}));

	assertType('array<string>', array_udiff($array2, $array1, static function (mixed $a, mixed $b) {
		assertType('string', $a);
		assertType('int', $b);

		return strcasecmp($a, (string) $b);
	}));

	assertType('array<int>', array_udiff($array1, $array3, static function (mixed $a, mixed $b) {
		assertType('int', $a);
		assertType('int', $b);

		return $a <=> $b;
	}));

	assertType('array<int, int>', array_udiff($array3, $array1, static function (mixed $a, mixed $b) {
		assertType('int', $a);
		assertType('int', $b);

		return $a <=> $b;
	}));

	assertType('array<int>', array_udiff($array1, $list, static function (mixed $a, mixed $b) {
		assertType('int', $a);
		assertType('string', $b);

		return strcasecmp((string) $a, $b);
	}));

	assertType('array<int<0, max>, string>', array_udiff($list, $array1, static function (mixed $a, mixed $b) {
		assertType('string', $a);
		assertType('int', $b);

		return strcasecmp($a, (string) $b);
	}));

	assertType('array<int>', array_udiff($nonEmptyArray, $array1, static function (mixed $a, mixed $b) {
		assertType('int', $a);
		assertType('int', $b);

		return $a <=> $b;
	}));

	assertType('array<int>', array_udiff($array1, $nonEmptyArray, static function (mixed $a, mixed $b) {
		assertType('int', $a);
		assertType('int', $b);

		return $a <=> $b;
	}));

	assertType('array<int>', array_udiff($array1, $arrayShape1, static function (mixed $a, mixed $b) {
		assertType('int', $a);
		assertType('int|string', $b);

		return $a <=> $b;
	}));

	assertType("array<0|'foo', int|string>", array_udiff($arrayShape1, $array1, static function (mixed $a, mixed $b) {
		assertType('int|string', $a);
		assertType('int', $b);

		return $a <=> $b;
	}));

	assertType("array<'bar'|'foo', int|string>", array_udiff($arrayShape2, $array1, static function (mixed $a, mixed $b) {
		assertType('int|string', $a);
		assertType('int', $b);

		return $a <=> $b;
	}));
}
