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
		assertType('array<int, (int|string)>', array_keys($arr));
		assertType('array<int, mixed>', array_values($arr));

		/** @var non-empty-array<string, int> $arr */
		shuffle($arr);
		assertType('non-empty-array<string, int>', $arr);
		assertType('non-empty-array<int, string>', array_keys($arr));
		assertType('non-empty-array<int, int>', array_values($arr));
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
		assertType('array<0|1|2, 1|2|3>', $arr);
		assertType('array<int, 0|1|2>', array_keys($arr));
		assertType('array<int, 1|2|3>', array_values($arr));

		/** @var array{1, 2, 3} $arr */
		shuffle($arr);
		assertType('non-empty-array<0|1|2, 1|2|3>', $arr);
		assertType('non-empty-array<int, 0|1|2>', array_keys($arr));
		assertType('non-empty-array<int, 1|2|3>', array_values($arr));

		/** @var array{a: 1, b: 2, c: 3} $arr */
		shuffle($arr);
		assertType('non-empty-array<0|1|2, 1|2|3>', $arr);
		assertType('non-empty-array<int, 0|1|2>', array_keys($arr));
		assertType('non-empty-array<int, 1|2|3>', array_values($arr));

		/** @var array{0: 1, 3: 2, 42: 3} $arr */
		shuffle($arr);
		assertType('non-empty-array<0|1|2, 1|2|3>', $arr);
		assertType('non-empty-array<int, 0|1|2>', array_keys($arr));
		assertType('non-empty-array<int, 1|2|3>', array_values($arr));
	}

}
