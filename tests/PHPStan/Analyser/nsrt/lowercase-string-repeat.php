<?php

namespace LowercaseStringStrRepeat;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param lowercase-string $lowercase
	 */
	public function doRepeat(string $string, string $lowercase): void
	{
		assertType('lowercase-string', str_repeat($lowercase, 5));
		assertType('string', str_repeat($string, 5));
	}

}
