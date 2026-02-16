<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug13759;

use function PHPStan\Testing\assertType;

class Test
{
	public function scenario(): void
	{
		$ints = [];
		foreach (['a', 'b'] as $key) {
			$ints[$key] = 1;
		}
		$ints['c'] = 1;

		assertType("array{a?: 1, b?: 1, c: 1}", $ints);

		foreach (['a'] as $key) {
			$ints[$key] = $this->intToSomething($ints[$key]);
		}

		assertType("array{a: float|string, b?: 1, c: 1}", $ints);
	}

	/**
	 * @return string|float
	 */
	protected function intToSomething(int $int): string|float {
		return mt_rand(1, 2) ? (string)$int : (float)$int;
	}
}
