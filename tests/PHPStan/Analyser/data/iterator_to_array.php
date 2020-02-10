<?php

namespace IteratorToArray;

use Traversable;
use function iterator_to_array;
use function PHPStan\Analyser\assertType;

class Foo
{
	/**
	 * @param Traversable<string, int> $ints
	 */
	public function doFoo(Traversable $ints)
	{
		assertType('array<string, int>', iterator_to_array($ints));
	}
}
