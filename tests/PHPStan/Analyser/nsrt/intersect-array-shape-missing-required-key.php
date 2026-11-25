<?php declare(strict_types = 1);

namespace IntersectArrayShapeMissingRequiredKey;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array<int, 0> $a
	 * @param array{0: int, k1: int} $b
	 */
	public function stringKeyInIntKeyedArray(array $a, array $b): void
	{
		if ($a === $b) {
			assertType('*NEVER*', $a);
		}
	}

	/**
	 * @param array<string, int> $a
	 * @param array{k: int, 0: int} $b
	 */
	public function intKeyInStringKeyedArray(array $a, array $b): void
	{
		if ($a === $b) {
			assertType('*NEVER*', $a);
		}
	}

	/**
	 * @param array<string, int> $a
	 * @param array{k: int, 0?: int} $b
	 */
	public function optionalKeyIsDropped(array $a, array $b): void
	{
		if ($a === $b) {
			assertType('array{k: int}', $a);
		}
	}

	/**
	 * @param array<int, int> $a
	 * @param array{0: int, 1: int} $b
	 */
	public function keysTheArrayCanHave(array $a, array $b): void
	{
		if ($a === $b) {
			assertType('array{int, int}', $a);
		}
	}

	/** @param array<int, 0>&array{0: int, k1: int} $x */
	public function phpDocIntersection($x): void
	{
		assertType('*NEVER*', $x);
	}

}
