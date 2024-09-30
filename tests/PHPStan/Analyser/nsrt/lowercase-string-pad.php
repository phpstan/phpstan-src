<?php

namespace LowercaseStringStrPad;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param lowercase-string $lowercase
	 */
	public function doRepeat(string $string, string $lowercase): void
	{
		assertType('non-empty-string', str_pad($string, 5));
		assertType('non-empty-string', str_pad($string, 5, $lowercase));
		assertType('non-empty-string', str_pad($string, 5, $string));
		assertType('lowercase-string&non-empty-string', str_pad($lowercase, 5));
		assertType('lowercase-string&non-empty-string', str_pad($lowercase, 5, $lowercase));
		assertType('non-empty-string', str_pad($lowercase, 5, $string));
	}

}
