<?php declare(strict_types = 1);

namespace Bug6189;

use Generator;

class Foo
{

	/**
	 * @return Generator<int, int, null, void>
	 */
	public function generatorWithYield(): Generator
	{
		while (true) {
			yield 1;
		}
	}

	/**
	 * @return Generator<int, int, null, void>
	 */
	public function generatorWithYieldFrom(): Generator
	{
		while (true) {
			yield from [1, 2, 3];
		}
	}

	/** Still an infinite loop - no yield inside the while body */
	public function noYieldInLoop(): void
	{
		while (true) {

		}
	}

	/**
	 * @return Generator<int, int, null, void>
	 */
	public function generatorWithYieldOutsideLoop(): Generator
	{
		yield 0;
		while (true) {
			// yield is outside the loop, so this is still an infinite loop
		}
	}

	/**
	 * @return Generator<int, int, null, void>
	 */
	public function generatorDoWhileWithYield(): Generator
	{
		do {
			yield 1;
		} while (true);
	}

}
