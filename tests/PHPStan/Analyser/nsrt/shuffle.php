<?php declare(strict_types = 1);

namespace Shuffle;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class Foo
{

	public function normalArrays1(array $arr): void
	{
		/** @var mixed[] $arr */
		shuffle($arr);
		assertType('list<mixed>', $arr);
		assertNativeType('list<mixed>', $arr);
		assertType('list<int<0, max>>', array_keys($arr));
		assertType('list<mixed>', array_values($arr));
	}

	public function normalArrays2(array $arr): void
	{
		/** @var non-empty-array<string, int> $arr */
		shuffle($arr);
		assertType('non-empty-list<int>', $arr);
		assertNativeType('list<mixed>', $arr);
		assertType('non-empty-list<int<0, max>>', array_keys($arr));
		assertType('non-empty-list<int>', array_values($arr));
	}

	public function normalArrays3(array $arr): void
	{
		/** @var array<mixed> $arr */
		if (array_key_exists('foo', $arr)) {
			shuffle($arr);
			assertType('non-empty-list<mixed>', $arr);
			assertNativeType('non-empty-list<mixed>', $arr);
			assertType('non-empty-list<int<0, max>>', array_keys($arr));
			assertType('non-empty-list<mixed>', array_values($arr));
		}
	}

	public function normalArrays4(array $arr): void
	{
		/** @var array<mixed> $arr */
		if (array_key_exists('foo', $arr) && $arr['foo'] === 'bar') {
			shuffle($arr);
			assertType('non-empty-list<mixed>', $arr);
			assertNativeType('non-empty-list<mixed>', $arr);
			assertType('non-empty-list<int<0, max>>', array_keys($arr));
			assertType('non-empty-list<mixed>', array_values($arr));
		}
	}

	public function constantArrays1(array $arr): void
	{
		$arr = [];
		shuffle($arr);
		assertType('array{}', $arr);
		assertNativeType('array{}', $arr);
		assertType('array{}', array_keys($arr));
		assertType('array{}', array_values($arr));
	}

	public function constantArrays2(array $arr): void
	{
		/** @var array{0?: 1, 1?: 2, 2?: 3} $arr */
		shuffle($arr);
		assertType('array<0|1|2, 1|2|3>&list', $arr);
		assertNativeType('list<mixed>', $arr);
		assertType('list<0|1|2>', array_keys($arr));
		assertType('list<1|2|3>', array_values($arr));
	}

	public function constantArrays3(array $arr): void
	{
		$arr = [1, 2, 3];
		shuffle($arr);
		assertType('non-empty-array<0|1|2, 1|2|3>&list', $arr);
		assertNativeType('non-empty-array<0|1|2, 1|2|3>&list', $arr);
		assertType('non-empty-list<0|1|2>', array_keys($arr));
		assertType('non-empty-list<1|2|3>', array_values($arr));
	}

	public function constantArrays4(array $arr): void
	{
		$arr = ['a' => 1, 'b' => 2, 'c' => 3];
		shuffle($arr);
		assertType('non-empty-array<0|1|2, 1|2|3>&list', $arr);
		assertNativeType('non-empty-array<0|1|2, 1|2|3>&list', $arr);
		assertType('non-empty-list<0|1|2>', array_keys($arr));
		assertType('non-empty-list<1|2|3>', array_values($arr));
	}

	public function constantArrays5(array $arr): void
	{
		$arr = [0 => 1, 3 => 2, 42 => 3];
		shuffle($arr);
		assertType('non-empty-array<0|1|2, 1|2|3>&list', $arr);
		assertNativeType('non-empty-array<0|1|2, 1|2|3>&list', $arr);
		assertType('non-empty-list<0|1|2>', array_keys($arr));
		assertType('non-empty-list<1|2|3>', array_values($arr));
	}

	public function constantArrays6(array $arr): void
	{
		/** @var array{foo?: 1, bar: 2, }|array{baz: 3, foobar?: 4} $arr */
		shuffle($arr);
		assertType('non-empty-array<0|1, 1|2|3|4>&list', $arr);
		assertNativeType('list<mixed>', $arr);
		assertType('non-empty-list<0|1>', array_keys($arr));
		assertType('non-empty-list<1|2|3|4>', array_values($arr));
	}

	public function mixed($arr): void
	{
		shuffle($arr);
		assertType('list<mixed>', $arr);
		assertNativeType('list<mixed>', $arr);
		assertType('list<int<0, max>>', array_keys($arr));
		assertType('list<mixed>', array_values($arr));
	}

	public function subtractedArray($arr): void
	{
		if (is_array($arr)) {
			shuffle($arr);
			assertType('list<mixed>', $arr);
			assertNativeType('list<mixed>', $arr);
			assertType('list<int<0, max>>', array_keys($arr));
			assertType('list<mixed>', array_values($arr));
		} else {
			shuffle($arr);
			assertType('*ERROR*', $arr);
			assertNativeType('*ERROR*', $arr);
			assertType('list<int|string>', array_keys($arr));
			assertType('list<mixed>', array_values($arr));
		}
	}

}
