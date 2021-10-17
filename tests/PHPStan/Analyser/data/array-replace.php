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
		assertType("array<int, bool|float|int|string>", array_replace($array1, $array2));
		assertType("array<int, bool|float|int|string>", array_replace($array2, $array1));
	}

	/**
	 * @param array<int, array{bar: '2'}|array{foo: '1'}> $array1
	 * @param array<int, array{bar: '3'}|array{foo: '2'}> $array2
	 */
	public function arrayReplaceUnionTypeArrayShapes($array1, $array2): void
	{
		assertType("array<int, array('bar' => '2')|array('foo' => '1')>", array_replace($array1, $array1));
		assertType("array<int, array('bar' => '2'|'3')|array('foo' => '1'|'2')>", array_replace($array1, $array2));
		assertType("array<int, array('bar' => '2'|'3')|array('foo' => '1'|'2')>", array_replace($array2, $array1));
	}
}