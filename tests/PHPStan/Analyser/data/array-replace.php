<?php

namespace NonEmptyArrayReplace;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param array $array1
	 * @param array $array2
	 */
	public function arrayReplace($array1, $array2): void
	{
		assertType("array", array_replace($array1, []));
		assertType("array", array_replace([], $array1));
		assertType("array", array_replace($array1, $array2));
	}

	/**
	 * @param array{foo: '1', bar: '2'} $array1
	 * @param array{foo: '1', bar: '4'} $array2
	 */
	public function arrayReplaceArrayShapes($array1, $array2): void
	{
		assertType("non-empty-array<'bar'|'foo', '1'|'2'>", array_replace($array1));
		assertType("non-empty-array<'bar'|'foo', '1'|'2'>", array_replace([], $array1));
		assertType("non-empty-array<'bar'|'foo', '1'|'2'|'4'>", array_replace($array1, $array2));
	}

	/**
	 * @param int[] $array1
	 * @param string[] $array2
	 */
	public function arrayReplaceSimple($array1, $array2): void
	{
		assertType("array<int>", array_replace($array1, $array1));
		assertType("array<int|string>", array_replace($array1, $array2));
		assertType("array<int|string>", array_replace($array2, $array1));
	}

	/**
	 * @param int[] ...$arrays1
	 */
	public function arrayReplaceVariadic(...$arrays1): void
	{
		assertType("array<int>", array_replace(...$arrays1));
	}

	/**
	 * @param array<int, int|string> $array1
	 * @param array<int, bool|float> $array2
	 */
	public function arrayReplaceUnionType($array1, $array2): void
	{
		assertType("array<int, int|string>", array_replace($array1, $array1));
		assertType("array<int, bool|float|int|string>", array_replace($array1, $array2));
		assertType("array<int, bool|float|int|string>", array_replace($array2, $array1));
	}

	/**
	 * @param array<int, array{bar: '2'}|array{foo: '1'}> $array1
	 * @param array<int, array{bar: '3'}|array{foo: '2'}> $array2
	 */
	public function arrayReplaceUnionTypeArrayShapes($array1, $array2): void
	{
		assertType("array<int, array{bar: '2'}|array{foo: '1'}>", array_replace($array1, $array1));
		assertType("array<int, array{bar: '2'}|array{bar: '3'}|array{foo: '1'}|array{foo: '2'}>", array_replace($array1, $array2));
		assertType("array<int, array{bar: '2'}|array{bar: '3'}|array{foo: '1'}|array{foo: '2'}>", array_replace($array2, $array1));
	}
}
