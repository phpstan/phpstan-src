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
		assertType("'de'|'en'", substr($language, 0, 2));
		assertType("'de_DE'|'en_US'", substr($language, 0, 10));

		assertType("'DE'|'US'", substr($language, 3));
		assertType("'_DE'|'_US'", substr($language, -3));
		assertType("'_'", substr($language, -3, 1));

		assertType("''", substr('de_DE', 10, -10));
	}
}
