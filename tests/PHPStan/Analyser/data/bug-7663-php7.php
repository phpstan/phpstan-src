<?php

namespace Bug7663;

use function PHPStan\Testing\assertType;

class HelloWorld7
{
	/**
	 * @param 'de_DE'|'en_US' $language
	 */
	public function sayHello($language): void
	{
		assertType("false", substr('de_DE', 10, -10));
	}
}
