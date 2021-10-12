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
		assertType('non-empty-array<int, int|string>', $result);
	}

	/**
	 * @param non-empty-array<string, int> $array
	 * @param non-empty-array<int, bool> $other
	 */
	public function arrayMapNull(array $array, array $other): void
	{
		assertType('array{}', array_map(null, []));
		assertType('array{foo: true}', array_map(null, ['foo' => true]));
		assertType('non-empty-array<int, array{1|2|3, 4|5|6}>', array_map(null, [1, 2, 3], [4, 5, 6]));

		assertType('non-empty-array<string, int>', array_map(null, $array));
		assertType('non-empty-array<int, array{int, int}>', array_map(null, $array, $array));
		assertType('non-empty-array<int, array{int, bool}>', array_map(null, $array, $other));
	}

}
