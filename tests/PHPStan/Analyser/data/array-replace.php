<?php

namespace NonEmptyArray;

use function PHPStan\Testing\assertType;

class Foo
{

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
	 * @param array<int, int|string> $array1
	 * @param array<int, bool|float> $array2
	 */
	public function arrayReplaceUnionType($array1, $array2): void
	{
		assertType("array<int, int|string>", array_replace($array1, $array1));
		assertType("array<int, int|string>", array_replace($array1, $array1, $array1, $array1));
		assertType("array<int, bool|float|int|string>", array_replace($array1, $array2));
		assertType("array<int, bool|float|int|string>", array_replace($array2, $array1));
	}

	/**
	 * @param array<int, array{bar: '2'}|array{foo: '1'}> $array1
	 * @param array<int, array{bar: '3'}|array{foo: '2'}> $array2
	 */
	public function arrayReplaceUnionTypeArrayShapes($array1, $array2): void
	{
		assertType("array<int, array{bar: '2'}|array{foo: '1'}>", array_merge($array1, $array1));
		assertType("array<int, array{bar: '2'|'3'}|array{foo: '1'|'2'}>", array_merge($array1, $array2));
		assertType("array<int, array{bar: '2'|'3'}|array{foo: '1'|'2'}>", array_merge($array2, $array1));
	}

	/**
	 * @param array{foo: '1'} $array1
	 * @param array{foo: '2'} $array2
	 */
	public function arrayReplaceUnionTypeArrayShapesSimple($array1, $array2): void
	{
		assertType("non-empty-array<'foo', '1'>", array_replace($array1, $array1));
		assertType("non-empty-array<'foo', '2'>", array_replace($array1, $array2));
		assertType("non-empty-array<'foo', '1'>", array_replace($array2, $array1));
	}

	public function arrayReplaceLogic(): void
	{
		$base = ["orange", "banana", "apple", 'foo' => "raspberry"];
		$replacements = [0 => "pineapple", 4 => "cherry", 'foo' => "lall"];
		$replacements2 = [0 => "grape"];

		assertType("non-empty-array<0|1|2|4|'foo', 'apple'|'banana'|'cherry'|'grape'|'lall'>", array_replace($base, $replacements, $replacements2));
	}
}