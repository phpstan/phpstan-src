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

}
