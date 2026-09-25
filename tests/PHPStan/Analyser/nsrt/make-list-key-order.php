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
	 * @param array{a?: bool, 1?: int, 0?: string} $withStringKey
	 * @param array{-1?: bool, 0?: string, 1: int} $withNegativeKey
	 * @param array{1?: int, 0?: string, ...<int, float>} $withExtras
	 */
	public function keysThatAListCannotHave(array $withStringKey, array $withNegativeKey, array $withExtras): void
	{
		if (array_is_list($withStringKey)) {
			assertType('list{0?: string, 1?: int}', $withStringKey);
			assertType('list{0?: string, 1?: int}', array_values($withStringKey));
		}

		if (array_is_list($withNegativeKey)) {
			assertType('array{string, int}', $withNegativeKey);
		}

		if (array_is_list($withExtras)) {
			assertType('list{0?: string, 1?: int, ...<float>}', $withExtras);
		}
	}

	/**
	 * @param array{0?: string, 2?: int, 3: bool} $requiredPastAGap
	 */
	public function requiredKeyPastAGap(array $requiredPastAGap): void
	{
		if (array_is_list($requiredPastAGap)) {
			// a list with the key 3 has the key 1 too, which this shape never has
			assertType('*NEVER*', $requiredPastAGap);
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
