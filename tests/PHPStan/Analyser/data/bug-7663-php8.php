<?php

namespace Bug7663;

use function PHPStan\Testing\assertType;

class HelloWorld8
{
	/**
	 * @param 'de_DE'|'en_US' $language
	 */
	public function sayHello($language): void
	{
		assertType("''", substr('de_DE', 10, -10));
	}
}
