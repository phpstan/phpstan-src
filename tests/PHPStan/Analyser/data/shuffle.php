<?php declare(strict_types = 1);

namespace Shuffle;

use function PHPStan\Testing\assertType;

class Foo
{

	public function normalArrays(array $arr): void
	{
		/** @var mixed[] $arr */
		shuffle($arr);
		assertType('array', $arr);
		assertType('list<(int|string)>', array_keys($arr));
		assertType('list<mixed>', array_values($arr));

		/** @var non-empty-array<string, int> $arr */
		shuffle($arr);
		assertType('non-empty-array<string, int>', $arr);
		assertType('non-empty-list<string>', array_keys($arr));
		assertType('non-empty-list<int>', array_values($arr));

		/** @var array<mixed> $arr */
		if (array_key_exists('foo', $arr)) {
			shuffle($arr);
			assertType("array&hasOffset('foo')", $arr);
			assertType('non-empty-list<(int|string)>', array_keys($arr));
			assertType('non-empty-list<mixed>', array_values($arr));
		}
	}

	public function constantArrays(array $arr): void
	{
		/** @var array{} $arr */
		shuffle($arr);
		assertType('array{}', $arr);
		assertType('array{}', array_keys($arr));
		assertType('array{}', array_values($arr));

		/** @var array{0?: 1, 1?: 2, 2?: 3} $arr */
		shuffle($arr);
		assertType('array<0|1|2, 1|2|3>&list', $arr);
		assertType('list<0|1|2>', array_keys($arr));
		assertType('list<1|2|3>', array_values($arr));

		/** @var array{1, 2, 3} $arr */
		shuffle($arr);
		assertType('non-empty-array<0|1|2, 1|2|3>&list', $arr);
		assertType('non-empty-list<0|1|2>', array_keys($arr));
		assertType('non-empty-list<1|2|3>', array_values($arr));

		/** @var array{a: 1, b: 2, c: 3} $arr */
		shuffle($arr);
		assertType('non-empty-array<0|1|2, 1|2|3>&list', $arr);
		assertType('non-empty-list<0|1|2>', array_keys($arr));
		assertType('non-empty-list<1|2|3>', array_values($arr));

		/** @var array{0: 1, 3: 2, 42: 3} $arr */
		shuffle($arr);
		assertType('non-empty-array<0|1|2, 1|2|3>&list', $arr);
		assertType('non-empty-list<0|1|2>', array_keys($arr));
		assertType('non-empty-list<1|2|3>', array_values($arr));

		/** @var array{foo?: 1, bar: 2, }|array{baz: 3, foobar?: 4} $arr */
		shuffle($arr); // no effect, constant array unions are not supported _yet_
		assertType("array{baz: 3, foobar?: 4}|array{foo?: 1, bar: 2}", $arr);
		assertType("array{0: 'baz', 1?: 'foobar'}|array{0?: 'foo', 1: 'bar'}", array_keys($arr));
		assertType("array{0: 3, 1?: 4}|array{0?: 1, 1: 2}", array_values($arr));
	}

	public function mixed($arr): void
	{
		shuffle($arr);
		assertType('mixed', $arr);
		assertType('list<int|string>', array_keys($arr));
		assertType('list<mixed>', array_values($arr));
	}

}
