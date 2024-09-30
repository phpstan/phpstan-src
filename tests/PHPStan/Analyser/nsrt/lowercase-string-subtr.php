<?php // lint >= 8.0

namespace LowercaseStringSubstr;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param lowercase-string $lowercase
	 */
	public function doSubstr(string $lowercase): void
	{
		assertType('lowercase-string', substr($lowercase, 5));
		assertType('lowercase-string', substr($lowercase, -5));
		assertType('lowercase-string', substr($lowercase, 0, 5));
	}

	/**
	 * @param lowercase-string $lowercase
	 */
	public function doMbSubstr(string $lowercase): void
	{
		assertType('lowercase-string', mb_substr($lowercase, 5));
		assertType('lowercase-string', mb_substr($lowercase, -5));
		assertType('lowercase-string', mb_substr($lowercase, 0, 5));
	}

}
