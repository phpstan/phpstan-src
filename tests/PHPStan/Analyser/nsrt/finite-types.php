<?php

namespace FiniteTypes;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(bool $a, bool $b): void
	{
		$array = [$a, $b];
		if ($array === [true, false]) {
			assertType('array{true, false}', $array);
			return;
		}

		assertType('array{false, false}|array{false, true}|array{true, true}', $array);
		if ($array === [false, false]) {
			assertType('array{false, false}', $array);
			return;
		}

		assertType('array{bool, true}', $array);
		if ($array === [true, true]) {
			assertType('array{true, true}', $array);
			return;
		}

		assertType('array{false, true}', $array);
	}

}
