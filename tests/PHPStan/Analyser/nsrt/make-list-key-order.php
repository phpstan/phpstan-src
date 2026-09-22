<?php

namespace MakeListKeyOrder;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array{1?: int, 0?: string} $descending
	 */
	public function keysInAscendingOrder(array $descending): void
	{
		if (array_is_list($descending)) {
			// a list has its keys in ascending order, so the value of 0 comes first
			assertType('list{0?: string, 1?: int}', $descending);
			assertType('list{0?: string, 1?: int}', array_values($descending));
		}
	}

	/**
	 * @param array{0?: string, 1: int} $optionalBelowRequired
	 * @param list{0: string, 1?: string, 2?: string, 3: string} $gapInList
	 * @param 1|2 $i
	 */
	public function keysBelowARequiredKeyAreRequired(array $optionalBelowRequired, array $gapInList, int $i): void
	{
		if (array_is_list($optionalBelowRequired)) {
			// a list with the key 1 has the key 0 too
			assertType('array{string, int}', $optionalBelowRequired);
		}

		assertType('array{string, string, string, string}', $gapInList);
		unset($gapInList[$i]);
		assertType('false', array_is_list($gapInList));
	}

}
