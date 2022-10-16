<?php declare(strict_types = 1);

namespace Shuffle;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class Foo
{

	public function normalArrays(array $arr): void
	{
		/** @var mixed[] $arr */
		shuffle($arr);
		assertType('array', $arr);
		assertNativeType('array', $arr);
		assertType('list<(int|string)>', array_keys($arr));
		assertType('list<mixed>', array_values($arr));

		/** @var non-empty-array<string, int> $arr */
		shuffle($arr);
		assertType('non-empty-array<string, int>', $arr);
		assertNativeType('array', $arr);
		assertType('non-empty-list<string>', array_keys($arr));
		assertType('non-empty-list<int>', array_values($arr));

		/** @var array<mixed> $arr */
		if (array_key_exists('foo', $arr)) {
			shuffle($arr);
			assertType('non-empty-array', $arr);
			assertNativeType('non-empty-array', $arr);
			assertType('non-empty-list<(int|string)>', array_keys($arr));
			assertType('non-empty-list<mixed>', array_values($arr));
		}
	}

	public function constantArrays(array $arr): void
	{
		$arr = [];
		shuffle($arr);
		assertType('array{}', $arr);
		assertNativeType('array{}', $arr);
		assertType('array{}', array_keys($arr));
		assertType('array{}', array_values($arr));

		/** @var array{0?: 1, 1?: 2, 2?: 3} $arr */
		shuffle($arr);
		assertType('array<0|1|2, 1|2|3>&list', $arr);
		assertNativeType('array', $arr);
		assertType('list<0|1|2>', array_keys($arr));
		assertType('list<1|2|3>', array_values($arr));

		$arr = [1, 2, 3];
		shuffle($arr);
		assertType('non-empty-array<0|1|2, 1|2|3>&list', $arr);
		assertNativeType('non-empty-array<0|1|2, 1|2|3>&list', $arr);
		assertType('non-empty-list<0|1|2>', array_keys($arr));
		assertType('non-empty-list<1|2|3>', array_values($arr));

		$arr = ['a' => 1, 'b' => 2, 'c' => 3];
		shuffle($arr);
		assertType('non-empty-array<0|1|2, 1|2|3>&list', $arr);
		assertNativeType('non-empty-array<0|1|2, 1|2|3>&list', $arr);
		assertType('non-empty-list<0|1|2>', array_keys($arr));
		assertType('non-empty-list<1|2|3>', array_values($arr));

		$arr = [0 => 1, 3 => 2, 42 => 3];
		shuffle($arr);
		assertType('non-empty-array<0|1|2, 1|2|3>&list', $arr);
		assertNativeType('non-empty-array<0|1|2, 1|2|3>&list', $arr);
		assertType('non-empty-list<0|1|2>', array_keys($arr));
		assertType('non-empty-list<1|2|3>', array_values($arr));

		/** @var array{foo?: 1, bar: 2, }|array{baz: 3, foobar?: 4} $arr */
		shuffle($arr);
		assertType('non-empty-array<0|1, 1|2|3|4>&list', $arr);
		assertNativeType('array', $arr);
		assertType('non-empty-list<0|1>', array_keys($arr));
		assertType('non-empty-list<1|2|3|4>', array_values($arr));
	}

	public function mixed($arr): void
	{
		shuffle($arr);
		assertType('array', $arr);
		assertNativeType('array', $arr);
		assertType('list<(int|string)>', array_keys($arr));
		assertType('list<mixed>', array_values($arr));
	}

	public function arrayWithExistingOffset(array $arr): void
	{
		if (array_key_exists('foo', $arr)) {
			shuffle($arr);
			assertType('non-empty-array', $arr);
			assertNativeType('non-empty-array', $arr);
			assertType('non-empty-list<(int|string)>', array_keys($arr));
			assertType('non-empty-list<mixed>', array_values($arr));
		}

		if (array_key_exists('foo', $arr) && $arr['foo'] === 'bar') {
			shuffle($arr);
			assertType('non-empty-array', $arr);
			assertNativeType('non-empty-array', $arr);
			assertType('non-empty-list<(int|string)>', array_keys($arr));
			assertType('non-empty-list<mixed>', array_values($arr));
		}
	}

	public function subtractedArray($arr): void
	{
		if (is_array($arr)) {
			shuffle($arr);
			assertType('array', $arr);
			assertNativeType('array', $arr);
			assertType('list<(int|string)>', array_keys($arr));
			assertType('list<(int|string)>', array_keys($arr));
		} else {
			shuffle($arr);
			assertType('*ERROR*', $arr);
			assertNativeType('*ERROR*', $arr);
			assertType('list<int|string>', array_keys($arr));
			assertType('list<int|string>', array_keys($arr));
		}
	}

}
