<?php

declare(strict_types = 1);

namespace InArrayNonEmpty;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @phpstan-param list<string> $array
	 */
	public function sayHello(array $array): void
	{
		if(in_array("thing", $array, true)){
			assertType('non-empty-list<string>', $array);
		}
	}

	/** @param array<int> $haystack */
	public function nonConstantNeedle(int $needle, array $haystack): void
	{
		if (in_array($needle, $haystack, true)) {
			assertType('non-empty-array<int>', $haystack);
		}
	}
}
