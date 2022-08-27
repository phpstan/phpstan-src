<?php

namespace ArrayMerge2;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array{foo: '1', bar: '2', lall: '3', 2: '2', 3: '3'} $array1
	 * @param array{foo: '1', bar: '4', lall2: '3', 2: '4', 3: '6'} $array2
	 */
	public function arrayMergeArrayShapes($array1, $array2): void
	{
		assertType("array{foo: '1', bar: '2', lall: '3', 0: '2', 1: '3'}", array_merge($array1));
		assertType("array{foo: '1', bar: '2', lall: '3', 0: '2', 1: '3'}", array_merge([], $array1));
		assertType("array{foo: '1', bar: '2', lall: '3', 0: '2', 1: '3'}", array_merge($array1, []));
		assertType("array{foo: '1', bar: '2', lall: '3', 0: '2', 1: '3', 2: '2', 3: '3'}", array_merge($array1, $array1));
		assertType("array{foo: '1', bar: '4', lall: '3', 0: '2', 1: '3', lall2: '3', 2: '4', 3: '6'}", array_merge($array1, $array2));
		assertType("array{foo: '1', bar: '2', lall2: '3', 0: '4', 1: '6', lall: '3', 2: '2', 3: '3'}", array_merge($array2, $array1));
		assertType("array{foo: 3, bar: '2', lall2: '3', 0: '4', 1: '6', lall: '3', 2: '2', 3: '3'}", array_merge($array2, $array1, ['foo' => 3]));
		assertType("array{foo: 3, bar: '2', lall2: '3', 0: '4', 1: '6', lall: '3', 2: '2', 3: '3'}", array_merge($array2, $array1, ...[['foo' => 3]]));
	}

	/**
	 * @param int[] $array1
	 * @param string[] $array2
	 */
	public function arrayMergeSimple($array1, $array2): void
	{
		assertType("array<int>", array_merge($array1, $array1));
		assertType("array<int|string>", array_merge($array1, $array2));
		assertType("array<int|string>", array_merge($array2, $array1));
	}

	/**
	 * @param array<int, int|string> $array1
	 * @param array<int, bool|float> $array2
	 */
	public function arrayMergeUnionType($array1, $array2): void
	{
		assertType("array<int, int|string>", array_merge($array1, $array1));
		assertType("array<int, bool|float|int|string>", array_merge($array1, $array2));
		assertType("array<int, bool|float|int|string>", array_merge($array2, $array1));
	}

	/**
	 * @param array<int, array{bar: '2'}|array{foo: '1'}> $array1
	 * @param array<int, array{bar: '3'}|array{foo: '2'}> $array2
	 */
	public function arrayMergeUnionTypeArrayShapes($array1, $array2): void
	{
		assertType("array<int, array{bar: '2'}|array{foo: '1'}>", array_merge($array1, $array1));
		assertType("array<int, array{bar: '2'}|array{bar: '3'}|array{foo: '1'}|array{foo: '2'}>", array_merge($array1, $array2));
		assertType("array<int, array{bar: '2'}|array{bar: '3'}|array{foo: '1'}|array{foo: '2'}>", array_merge($array2, $array1));
	}
}
