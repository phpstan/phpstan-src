<?php

namespace ArraySlice;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param non-empty-array $a
	 * @param non-empty-list $b
	 * @param non-empty-array<int>|non-empty-list<string> $c
	 */
	public function nonEmpty(array $a, array $b, array $c): void
	{
		assertType('array', array_slice($a, 1));
		assertType('list<mixed>', array_slice($b, 1));
		assertType('array<int|string>', array_slice($c, 1));
	}

	/**
	 * @param mixed $arr
	 */
	public function fromMixed($arr): void
	{
		assertType('array', array_slice($arr, 1, 2));
	}

	public function normalArrays(array $arr): void
	{
		/** @var array<int, bool> $arr */
		assertType('array<int, bool>', array_slice($arr, 1, 2));
		assertType('array<int, bool>', array_slice($arr, 1, 2, true));

		/** @var array<string, int> $arr */
		assertType('array<string, int>', array_slice($arr, 1, 2));
		assertType('array<string, int>', array_slice($arr, 1, 2, true));
	}

	public function constantArrays(array $arr): void
	{
		/** @var array{17: 'foo', b: 'bar', 19: 'baz'} $arr */
		assertType('array{b: \'bar\', 0: \'baz\'}', array_slice($arr, 1, 2));
		assertType('array{b: \'bar\', 19: \'baz\'}', array_slice($arr, 1, 2, true));

		/** @var array{17: 'foo', 19: 'bar', 21: 'baz'}|array{foo: 17, bar: 19, baz: 21} $arr */
		assertType('array{\'bar\', \'baz\'}|array{bar: 19, baz: 21}', array_slice($arr, 1, 2));
		assertType('array{19: \'bar\', 21: \'baz\'}|array{bar: 19, baz: 21}', array_slice($arr, 1, 2, true));

		/** @var int $offset */
		/** @var array{17: 'foo', b: 'bar', 19: 'baz'} $arr */
		assertType('array', array_slice($arr, $offset, 2));
		assertType('array', array_slice($arr, $offset, 2, true));

		/** @var int $limit */
		/** @var array{17: 'foo', b: 'bar', 19: 'baz'} $arr */
		assertType('array', array_slice($arr, 1, $limit));
		assertType('array', array_slice($arr, 1, $limit, true));
	}

	public function constantArraysWithOptionalKeys(array $arr): void
	{
		/** @var array{a?: 0, b: 1, c: 2} $arr */
		assertType('array{a?: 0, b?: 1}', array_slice($arr, 0, 1));
		assertType('array{a?: 0, b: 1, c: 2}', array_slice($arr, 0));
		assertType('array{a?: 0, b: 1, c: 2}', array_slice($arr, -99));
		assertType('array{a?: 0, b: 1, c: 2}', array_slice($arr, 0, 99));
		assertType('array{a?: 0}', array_slice($arr, 0, -2));
		assertType('array{}', array_slice($arr, 0, -3));
		assertType('array{}', array_slice($arr, 0, -99));
		assertType('array{}', array_slice($arr, -99, -99));
		assertType('array{}', array_slice($arr, 99));

		/** @var array{a?: 0, b?: 1, c: 2, d: 3, e: 4} $arr */
		assertType('array{c?: 2, d?: 3, e?: 4}', array_slice($arr, 2, 1));
		assertType('array{b?: 1, c?: 2, d: 3, e?: 4}', array_slice($arr, 1, 3));
		assertType('array{e: 4}', array_slice($arr, -1));
		assertType('array{d: 3}', array_slice($arr, -2, 1));

		/** @var array{a: 0, b: 1, c: 2, d?: 3, e?: 4} $arr */
		assertType('array{c: 2}', array_slice($arr, 2, 1));
		assertType('array{c?: 2, d?: 3, e?: 4}', array_slice($arr, -1));
		assertType('array{b?: 1, c?: 2, d?: 3}', array_slice($arr, -2, 1));
		assertType('array{a: 0, b: 1, c?: 2, d?: 3}', array_slice($arr, 0, -1));
		assertType('array{a: 0, b?: 1, c?: 2}', array_slice($arr, 0, -2));

		/** @var array{a: 0, b?: 1, c: 2, d?: 3, e: 4} $arr */
		assertType('array{b?: 1, c: 2, d?: 3, e?: 4}', array_slice($arr, 1, 2));
		assertType('array{a: 0, b?: 1, c?: 2}', array_slice($arr, 0, 2));
		assertType('array{a: 0}', array_slice($arr, 0, 1));
		assertType('array{b?: 1, c?: 2}', array_slice($arr, 1, 1));
		assertType('array{c?: 2, d?: 3, e?: 4}', array_slice($arr, 2, 1));
		assertType('array{a: 0, b?: 1, c: 2, d?: 3}', array_slice($arr, 0, -1));
		assertType('array{c?: 2, d?: 3, e: 4}', array_slice($arr, -2));

		/** @var array{a: 0, b?: 1, c: 2} $arr */
		assertType('array{a: 0, b?: 1}', array_slice($arr, 0, -1));
		assertType('array{a: 0}', array_slice($arr, -3, 1));
	}

}
