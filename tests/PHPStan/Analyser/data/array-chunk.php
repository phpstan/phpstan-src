<?php declare(strict_types = 1);

namespace ArrayChunk;

use function PHPStan\Testing\assertType;

class Foo
{

	public function generalArrays(array $arr): void
	{
		/** @var mixed[] $arr */
		assertType('list<non-empty-list<mixed>>', array_chunk($arr, 2));
		assertType('list<non-empty-array>', array_chunk($arr, 2, true));

		/** @var array<string, int> $arr */
		assertType('list<non-empty-list<int>>', array_chunk($arr, 2));
		assertType('list<non-empty-array<string, int>>', array_chunk($arr, 2, true));

		/** @var non-empty-array<int|string, bool> $arr */
		assertType('non-empty-list<non-empty-list<bool>>', array_chunk($arr, 1));
		assertType('non-empty-list<non-empty-array<int|string, bool>>', array_chunk($arr, 1, true));
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

	/**
	 * @param int<2, 3> $positiveRange
	 * @param 2|3 $positiveUnion
	 */
	public function chunkUnionTypeLength(array $arr, $positiveRange, $positiveUnion) {
		/** @var array{a: 0, b?: 1, c: 2} $arr */
		assertType('array{0: array{0: 0, 1?: 1|2, 2?: 2}, 1?: array{0?: 2}}', array_chunk($arr, $positiveRange));
		assertType('array{0: array{a: 0, b?: 1, c?: 2}, 1?: array{c?: 2}}', array_chunk($arr, $positiveRange, true));
		assertType('array{0: array{0: 0, 1?: 1|2, 2?: 2}, 1?: array{0?: 2}}', array_chunk($arr, $positiveUnion));
		assertType('array{0: array{a: 0, b?: 1, c?: 2}, 1?: array{c?: 2}}', array_chunk($arr, $positiveUnion, true));
	}

	/**
	 * @param positive-int $positiveInt
	 * @param int<50, max> $bigger50
	 */
	public function lengthIntRanges(array $arr, int $positiveInt, int $bigger50) {
		assertType('list<non-empty-list<mixed>>', array_chunk($arr, $positiveInt));
		assertType('list<non-empty-list<mixed>>', array_chunk($arr, $bigger50));
	}

	/**
	 * @param int<1, 4> $oneToFour
	 * @param int<1, 5> $tooBig
	 */
	function testLimits(array $arr, int $oneToFour, int $tooBig) {
		/** @var array{a: 0, b?: 1, c: 2, d: 3} $arr */
		assertType('array{0: array{0: 0, 1?: 1|2, 2?: 2|3, 3?: 3}, 1?: array{0?: 2|3, 1?: 3}}|array{array{0}, array{0?: 1|2, 1?: 2}, array{0?: 2|3, 1?: 3}, array{0?: 3}}', array_chunk($arr, $oneToFour));
		assertType('non-empty-list<non-empty-list<0|1|2|3>>', array_chunk($arr, $tooBig));
	}


}
