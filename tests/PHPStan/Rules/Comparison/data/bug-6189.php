<?php

namespace Bug6189;

use Generator;

class Foo
{

	/**
	 * @return Generator<int, int, mixed, void>
	 */
	public function whileTrue(): Generator
	{
		$i = 0;
		while (true) {
			yield $i++;
		}
	}

	/**
	 * @return Generator<int, int, mixed, void>
	 */
	public function doWhileTrue(): Generator
	{
		$i = 0;
		do {
			yield $i++;
		} while (true);
	}

	/**
	 * @return Generator<int, string, mixed, void>
	 */
	public function whileTrueYieldInNested(): Generator
	{
		$i = 0;
		while (true) {
			if ($i % 2 === 0) {
				yield 'even';
			} else {
				yield 'odd';
			}
			$i++;
		}
	}

	/**
	 * @return Generator<int, int, mixed, void>
	 */
	public function foreverYieldFrom(): Generator
	{
		while (true) {
			yield from [1, 2, 3];
		}
	}

	// This should still be reported - no yield in infinite loop
	public function whileTrueNoYield(): void
	{
		while (true) {
			echo 'infinite';
		}
	}

	// This should still be reported - no yield in infinite loop
	public function doWhileTrueNoYield(): void
	{
		do {
			echo 'infinite';
		} while (true);
	}

}
