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

	/**
	 * @param non-empty-list<non-empty-string> $items
	 */
	public function movesArrayPointer(array $items): string
	{
		if (end($items) === 'x') {
			assertType("'x'", end($items));
		}
		if (reset($items) === 'x') {
			assertType("'x'", reset($items));
		}
		if (next($items) === 'x') {
			assertType('non-empty-string|false', next($items));
		}
		if (prev($items) === 'x') {
			assertType('non-empty-string|false', prev($items));
		}
		if (!end($items)) {
			return '';
		}

		return end($items);
	}

}
