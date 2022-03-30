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
		assertType("array{foo: '1', bar: '2'}", array_replace($array1));
		assertType("array{foo: '1', bar: '2'}", array_replace([], $array1));
		assertType("array{foo: '1', bar: '4'}", array_replace($array1, $array2));
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
		assertType("array<int, array{bar: '2'|'3'}|array{foo: '1'|'2'}>", array_replace($array1, $array2));
		assertType("array<int, array{bar: '2'|'3'}|array{foo: '1'|'2'}>", array_replace($array2, $array1));
	}
}

class FooBar
{
	/**
	 * @param array<non-empty-string, string> $params1
	 * @param array<non-empty-string, string> $params2
	 */
	function foo1(array $params1, array $params2): void
	{
		$params2 = array_replace($params1, $params2);

		assertType('array<non-empty-string, string>', $params2);
	}

	/**
	 * @param array<non-empty-string, string> $params1
	 * @param array<string, string> $params2
	 */
	function foo2(array $params1, array $params2): void
	{
		$params2 = array_replace($params1, $params2);

		assertType('array<string, string>', $params2);
	}

	/**
	 * @param array<string, string> $params1
	 * @param array<non-empty-string, string> $params2
	 */
	function foo3(array $params1, array $params2): void
	{
		$params2 = array_replace($params1, $params2);

		assertType('array<string, string>', $params2);
	}

	/**
	 * @param array<literal-string&non-empty-string, string> $params1
	 * @param array<non-empty-string, string> $params2
	 */
	function foo4(array $params1, array $params2): void
	{
		$params2 = array_replace($params1, $params2);

		assertType('array<non-empty-string, string>', $params2);
	}

	/**
	 * @param array{return: int, stdout: string, stderr: string} $params1
	 * @param array{return: int, stdout?: string, stderr?: string} $params2
	 */
	function foo5(array $params1, array $params2): void
	{
		$params3 = array_replace($params1, $params2);

		assertType('array{return: int, stdout: string, stderr: string}', $params3);
	}

}
