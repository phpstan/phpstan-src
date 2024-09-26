<?php

namespace LowercaseStringTrim;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param lowercase-string $lowercase
	 */
	public function doTrim(string $lowercase, string $string): void
	{
		assertType('lowercase-string', trim($lowercase));
		assertType('lowercase-string', ltrim($lowercase));
		assertType('lowercase-string', rtrim($lowercase));
		assertType('lowercase-string', trim($lowercase, $string));
		assertType('lowercase-string', ltrim($lowercase, $string));
		assertType('lowercase-string', rtrim($lowercase, $string));
		assertType('string', trim($string));
		assertType('string', ltrim($string));
		assertType('string', rtrim($string));
		assertType('string', trim($string, $lowercase));
		assertType('string', ltrim($string, $lowercase));
		assertType('string', rtrim($string, $lowercase));
	}

}
