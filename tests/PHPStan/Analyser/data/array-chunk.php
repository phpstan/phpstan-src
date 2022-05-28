<?php declare(strict_types = 1);

namespace ArrayChunk;

use function PHPStan\Testing\assertType;

class Foo
{

	public function generalArrays(array $arr): void
	{
		/** @var mixed[] $arr */
		assertType('array<int, array<int, mixed>>', array_chunk($arr, 2));
		assertType('array<int, array>', array_chunk($arr, 2, true));

		/** @var array<string, int> $arr */
		assertType('array<int, array<int, int>>', array_chunk($arr, 2));
		assertType('array<int, array<string, int>>', array_chunk($arr, 2, true));

		/** @var non-empty-array<int|string, bool> $arr */
		assertType('array<int, non-empty-array<int, bool>>', array_chunk($arr, 1));
		assertType('array<int, non-empty-array<int|string, bool>>', array_chunk($arr, 1, true));
	}


	public function constantArrays(array $arr): void
	{
		/** @var array{a: 0, 17: 1, b: 2} $arr */
		assertType('array{array{0, 1}, array{2}}', array_chunk($arr, 2));
		assertType('array{array{a: 0, 17: 1}, array{b: 2}}', array_chunk($arr, 2, true));
		assertType('array{array{0}, array{1}, array{2}}', array_chunk($arr, 1));
		assertType('array{array{a: 0}, array{17: 1}, array{b: 2}}', array_chunk($arr, 1, true));
	}

	public function constantArraysWithOptionalKeys(array $arr): void
	{
		/** @var array{a: 0, b?: 1, c: 2} $arr */
		assertType('array{array{a: 0, b?: 1, c?: 2}, array{c?: 2}}', array_chunk($arr, 2, true));
		assertType('array{array{a: 0, b?: 1, c: 2}}', array_chunk($arr, 3, true));
		assertType('array{array{a: 0}, array{b?: 1, c?: 2}, array{c?: 2}}', array_chunk($arr, 1, true));

		/** @var array{a?: 0, b?: 1, c?: 2} $arr */
		assertType('array{array{a?: 0, b?: 1, c?: 2}, array{c?: 2}}', array_chunk($arr, 2, true));
	}

}
