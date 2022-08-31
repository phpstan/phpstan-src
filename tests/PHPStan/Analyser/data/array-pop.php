<?php declare(strict_types = 1);

namespace ArrayPop;

use function PHPStan\Testing\assertType;

class Foo
{

	public function nonEmpty(array $arr): void
	{
		/** @var non-empty-array<string> $arr */
		assertType('string', array_pop($arr));
		assertType('array<string>', $arr);
	}

	public function normalArrays(array $arr): void
	{
		/** @var array<int, string> $arr */
		assertType('string|null', array_pop($arr));
		assertType('array<int, string>', $arr);
	}

	public function compoundTypes(array $arr): void
	{
		/** @var string[]|int[] $arr */
		assertType('int|string|null', array_pop($arr));
		assertType('array<int|string>', $arr);
	}

	public function constantArrays(array $arr): void
	{
		/** @var array{a: 0, b: 1, c: 2} $arr */
		assertType('2', array_pop($arr));
		assertType('array{a: 0, b: 1}', $arr);
	}

	public function constantArraysWithOptionalKeys(array $arr): void
	{
		/** @var array{a?: 0, b: 1, c: 2} $arr */
		assertType('2', array_pop($arr));
		assertType('array{a?: 0, b: 1}', $arr);

		/** @var array{a: 0, b?: 1, c: 2} $arr */
		assertType('2', array_pop($arr));
		assertType('array{a: 0, b?: 1}', $arr);

		/** @var array{a: 0, b: 1, c?: 2} $arr */
		assertType('1|2', array_pop($arr));
		assertType('array{a: 0, b?: 1}', $arr);
	}

}
