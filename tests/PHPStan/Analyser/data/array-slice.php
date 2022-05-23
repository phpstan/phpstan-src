<?php

namespace ArraySlice;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param non-empty-array $a
	 */
	public function nonEmpty(array $a): void
	{
		assertType('array', array_slice($a, 1));
	}

	/**
	 * @param mixed $arr
	 */
	public function fromMixed($arr): void
	{
		assertType('array', array_slice($arr, 1, 2));
	}

	/**
	 * @param array<int, bool> $arr1
	 * @param array<string, int> $arr2
	 * @param array{17: 'foo', b: 'bar', 19: 'baz'} $arr3
	 * @param array{17: 'foo', 19: 'bar', 21: 'baz'}|array{foo: 17, bar: 19, baz: 21} $arr4
	 */
	public function preserveTypes(array $arr1, array $arr2, array $arr3, array $arr4): void
	{
		assertType('array<int, bool>', array_slice($arr1, 1, 2));
		assertType('array<int, bool>', array_slice($arr1, 1, 2, true));
		assertType('array<string, int>', array_slice($arr2, 1, 2));
		assertType('array<string, int>', array_slice($arr2, 1, 2, true));
		assertType('array{b: \'bar\', 0: \'baz\'}', array_slice($arr3, 1, 2));
		assertType('array{b: \'bar\', 19: \'baz\'}', array_slice($arr3, 1, 2, true));
		assertType('array{\'bar\', \'baz\'}|array{bar: 19, baz: 21}', array_slice($arr4, 1, 2));
		assertType('array{19: \'bar\', 21: \'baz\'}|array{bar: 19, baz: 21}', array_slice($arr4, 1, 2, true));
	}

}
