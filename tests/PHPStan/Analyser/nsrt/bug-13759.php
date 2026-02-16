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

		assertType("non-empty-array<'a'|'b'|'c', 1>&hasOffsetValue('c', 1)", $ints);

		foreach (['a'] as $key) {
			$ints[$key] = $this->intToSomething($ints[$key]);
		}

		assertType("non-empty-array<'a'|'b'|'c', 1|float|string>&hasOffsetValue('a', float|string)&hasOffsetValue('c', 1)", $ints);
	}

	/**
	 * @return string|float
	 */
	protected function intToSomething(int $int): string|float {
		return mt_rand(1, 2) ? (string)$int : (float)$int;
	}
}
