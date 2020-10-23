<?php

namespace ArraySliceNonEmpty;

use function PHPStan\Analyser\assertType;

class Foo
{

	/**
	 * @param non-empty-array $a
	 */
	public function doFoo(array $a): void
	{
		assertType('array', array_slice($a, 1));
	}

}
