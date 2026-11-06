<?php

namespace ArrayUnpackingPhpVersionRange;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array{a: int, b: string} $shape
	 * @param array<string, int> $map
	 */
	public function doFoo(array $shape, array $map): void
	{
		// unpacking string keys is an Error below PHP 8.1, so the keys survive
		assertType('array{a: int, b: string}', [...$shape]);
		assertType('array<string, int>', [...$map]);
	}

}
