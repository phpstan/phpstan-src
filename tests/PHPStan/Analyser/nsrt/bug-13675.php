<?php

namespace Bug13675;

use function assert;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param list<int> $listA
	 * @param list<int> $listB
	 */
	public function sayHello(int $i, $listA, $listB): void
	{
		if (!isset($listA[$i])) {
			return;
		}
		assertType('non-empty-list<int>', $listA);

		if (count($listA) !== count($listB)) {
			return;
		}
		assertType('non-empty-list<int>', $listB);
	}
}

