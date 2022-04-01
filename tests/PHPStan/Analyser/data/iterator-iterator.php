<?php

namespace IteratorIteratorTest;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param \ArrayIterator<int, string> $it
	 */
	public function doFoo(\ArrayIterator $it): void
	{
		$iteratorIterator = new \IteratorIterator($it);
		assertType('Iterator<int, string>', $iteratorIterator->getInnerIterator());
		assertType('array<int, string>', $iteratorIterator->getArrayCopy());
	}

	/**
	 * @param \ArrayIterator<int, string> $it
	 */
	public function limit(\ArrayIterator $it): void
	{
		$limitIterator = new \LimitIterator($it);
		assertType('Iterator<int, string>', $limitIterator->getInnerIterator());
		assertType('array<int, string>', $limitIterator->getArrayCopy());
		assertType('array<int, string>', iterator_to_array($limitIterator));
	}

}
