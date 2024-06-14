<?php declare(strict_types = 1);

namespace ArrayShift;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class Foo
{

	public function nonEmpty(array $arr): void
	{
		/** @var non-empty-array<string> $arr */
		assertType('string', array_shift($arr));
		assertType('array<string>', $arr);
	}

	public function normalArrays(array $arr): void
	{
		/** @var array<int, string> $arr */
		assertType('string|null', array_shift($arr));
		assertType('array<int, string>', $arr);
	}

	public function compoundTypes(array $arr): void
	{
		/** @var string[]|int[] $arr */
		assertType('int|string|null', array_shift($arr));
		assertType('array<int|string>', $arr);
	}

	public function constantArrays(array $arr): void
	{
		/** @var array{a: 0, b: 1, c: 2} $arr */
		assertType('0', array_shift($arr));
		assertType('array{b: 1, c: 2}', $arr);

		/** @var array{} $arr */
		assertType('null', array_shift($arr));
		assertType('array{}', $arr);
	}

	public function constantArraysWithOptionalKeys(array $arr): void
	{
		/** @var array{a?: 0, b: 1, c: 2} $arr */
		assertType('0|1', array_shift($arr));
		assertType('array{b?: 1, c: 2}', $arr);

		/** @var array{a: 0, b?: 1, c: 2} $arr */
		assertType('0', array_shift($arr));
		assertType('array{b?: 1, c: 2}', $arr);

		/** @var array{a: 0, b: 1, c?: 2} $arr */
		assertType('0', array_shift($arr));
		assertType('array{b: 1, c?: 2}', $arr);

		/** @var array{a?: 0, b?: 1, c?: 2} $arr */
		assertType('0|1|2|null', array_shift($arr));
		assertType('array{b?: 1, c?: 2}', $arr);
	}

	public function list(array $arr): void
	{
		/** @var list<string> $arr */
		assertType('string|null', array_shift($arr));
		assertType('list<string>', $arr);
	}

	public function mixed($mixed): void
	{
		assertType('mixed', array_shift($mixed));
		assertType('array', $mixed);
	}

	public function foo1($mixed): void
	{
		if(is_array($mixed)) {
			assertType('mixed', array_shift($mixed));
		} else {
			assertType('mixed~array', $mixed);
			assertType('mixed', array_shift($mixed));
			assertType('*ERROR*', $mixed);
		}
	}

	/** @param non-empty-array<string> $arr1 */
	public function nativeTypes(array $arr1, array $arr2): void
	{
		assertType('string', array_shift($arr1));
		assertType('array<string>', $arr1);

		assertNativeType('mixed', array_shift($arr2));
		assertNativeType('array', $arr2);
	}
}
