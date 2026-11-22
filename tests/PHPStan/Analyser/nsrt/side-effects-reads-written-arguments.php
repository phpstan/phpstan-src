<?php declare(strict_types = 1);

namespace SideEffectsReadsWrittenArguments;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param list<int> $items
	 */
	public function readsWrittenArgument(array $items): void
	{
		if (array_pop($items) === 1) {
			assertType('int|null', array_pop($items));
		}
		if (array_unshift($items, 1) === 3) {
			assertType('int<1, max>', array_unshift($items, 1));
		}
	}

}
