<?php

namespace Bug3981;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param string $s
	 * @param non-empty-string $nonEmptyString
	 */
	public function doFoo(string $s, string $nonEmptyString): void
	{
		assertType('non-empty-string|false', strtok($s, ' '));
		assertType('non-empty-string', strtok($nonEmptyString, ' '));
		assertType('false', strtok('', ' '));

		assertType('non-empty-string', $nonEmptyString[0]);
		assertType('string', $nonEmptyString[1]);
		assertType('string', $s[0]);
	}

}
