<?php

namespace Bug6070;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @return non-empty-array
	 */
	public function getNonEmptyArray(): array {
		$nonEmptyArray = [rand()];

		for ($i = 0; $i < 2; $i++) {
			$nonEmptyArray[] = 1;
		}

		assertType('non-empty-list<int<0, max>>', $nonEmptyArray);

		return $nonEmptyArray;
	}
}
