<?php

namespace ArrayKeys;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello($mixed): void
	{
		if(is_array($mixed)) {
			assertType('list<(int|string)>', array_keys($mixed));
		} else {
			assertType('mixed~array', $mixed);
			assertType('*NEVER*', array_keys($mixed));
		}
	}

	/** @param array<int|string, bool|string> $arr1 */
	public function normalArrays(array $arr1, string $string, int $int): void
	{
		assertType('list<int|string>', array_keys($arr1));
		assertType('list<(int|string)>', array_keys($arr1, $string));
		assertType('list<int|string>', array_keys($arr1, $string, true));
		assertType('list<(int|string)>', array_keys($arr1, $int));
		assertType('array{}', array_keys($arr1, $int, true));

		if (array_key_exists(17, $arr1)) {
			assertType('array<int|string, bool|string>&hasOffset(17)', $arr1);
			assertType('list<int|string>', array_keys($arr1, true, true));
			assertType('list<int|string>', array_keys($arr1, 'foo', true));
		}

		if (array_key_exists(17, $arr1) && $arr1[17] === 'foo') {
			assertType("array<int|string, bool|string>&hasOffsetValue(17, 'foo')", $arr1);
			assertType('list<int|string>', array_keys($arr1, true, true));
			assertType('non-empty-list<int|string>', array_keys($arr1, 'foo', true));
		}
	}

	/** @param non-empty-array<int|string, bool|string> $arr1 */
	public function nonEmptyArrays(array $arr1, string $string, int $int): void
	{
		assertType('non-empty-list<int|string>', array_keys($arr1));
		assertType('list<(int|string)>', array_keys($arr1, $string));
		assertType('list<int|string>', array_keys($arr1, $string, true));
		assertType('list<(int|string)>', array_keys($arr1, $int));
		assertType('array{}', array_keys($arr1, $int, true));
	}

	/** @param array<int, bool>|array<string, bool> $arr1 */
	public function compoundTypes(array $arr1, bool $bool): void
	{
		assertType('list<int|string>', array_keys($arr1));
		assertType('list<(int|string)>', array_keys($arr1, $bool));
		assertType('list<(int|string)>', array_keys($arr1, $bool, $bool));
		assertType('list<int|string>', array_keys($arr1, $bool, true));
		assertType('list<(int|string)>', array_keys($arr1, 1));
		assertType('array{}', array_keys($arr1, 1, true));
	}

	/**
	 * @param array{} $arr1
	 * @param array{a: 0, b: 1, c?: 2} $arr2
	 */
	public function constantArrays(array $arr1, array $arr2, int $int): void
	{
		assertType('array{}', array_keys($arr1));

		assertType("array{0: 'a', 1: 'b', 2?: 'c'}", array_keys($arr2));
		assertType("array{0?: 'a'|'b'|'c', 1?: 'b'|'c', 2?: 'c'}", array_keys($arr2, $int, true));
		assertType("list<(int|string)>", array_keys($arr2, $int, false));
		assertType("array{'b'}", array_keys($arr2, 1, true));
		assertType("array{0?: 'a'|'b', 1?: 'b'}", array_keys($arr2, rand(0, 1), true));
		assertType("array{0?: 'c'}", array_keys($arr2, 2, true));
	}
}
