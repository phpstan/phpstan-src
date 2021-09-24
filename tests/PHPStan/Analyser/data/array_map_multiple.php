<?php

namespace ArrayMapMultiple;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(int $i, string $s): void
	{
		$result = array_map(function ($a, $b) {
			assertType('int', $a);
			assertType('string', $b);

			return rand(0, 1) ? $a : $b;
		}, ['foo' => $i], ['bar' => $s]);
		assertType('array<int, int|string>&nonEmpty', $result);
	}

	/**
	 * @param non-empty-array<string, int> $array
	 * @param non-empty-array<int, bool> $other
	 */
	public function arrayMapNull(array $array, array $other): void
	{
		assertType('array()', array_map(null, []));
		assertType('array(\'foo\' => true)', array_map(null, ['foo' => true]));
		assertType('array<int, array(1|2|3, 4|5|6)>&nonEmpty', array_map(null, [1, 2, 3], [4, 5, 6]));

		assertType('array<string, int>&nonEmpty', array_map(null, $array));
		assertType('array<int, array(int, int)>&nonEmpty', array_map(null, $array, $array));
		assertType('array<int, array(int, bool)>&nonEmpty', array_map(null, $array, $other));
	}

}
