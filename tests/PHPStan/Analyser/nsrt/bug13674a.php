<?php

namespace Bug13674a;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param array<int> $arrayA
	 * @param list<int> $listA
	 */
	public function sayHello($arrayA, $listA, int $i): void
	{
		if (array_key_exists($i, $arrayA)) {
			assertType('non-empty-array<int>', $arrayA);
		} else {
			assertType('array<int>', $arrayA);
		}
		assertType('array<int>', $arrayA);

		if (array_key_exists($i, $listA)) {
			assertType('non-empty-list<int>', $listA);
		} else {
			assertType('list<int>', $listA);
		}
		assertType('list<int>', $listA);
	}
}
