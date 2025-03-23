<?php declare(strict_types = 1);

namespace Bug3488;

interface I {
	const THING_A = 'a';
	const THING_B = 'b';
	const THING_C = 'c';
}

class C
{
	/**
	 * @param I::THING_* $thing
	 */
	public function allCovered(string $thing): int
	{
		switch ($thing) { // should not error
			case I::THING_A:
			case I::THING_B:
			case I::THING_C:
				return 0;
		}
	}
	/**
	 * @param I::THING_* $thing
	 */
	public function invalidCase(string $thing): int
	{
		switch ($thing) {
			case I::THING_A:
			case I::THING_B:
				return 0;
			case 'd':
				throw new Exception('The error marker should be on the line above');
		}
	}
	/**
	 * @param I::THING_* $thing
	 */
	public function defaultUnnecessary(string $thing): int
	{
		switch ($thing) {
			case I::THING_A:
			case I::THING_B:
			case I::THING_C:
				return 0;
			default:
				throw new Exception('This should be detected as unreachable');
		}
	}
}
