<?php

namespace Bug7663;

declare(strict_types=1);

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param 'de_DE'|'en_US' $language
	 */
	public function sayHello($language): void
	{
		assertType("'de'|'en'", substr($language, 0, 2));
		assertType("'de_DE'|'en_US'", substr($language, 0, 10));

		assertType("'DE'|'US'", substr($language, 3));
	}
}
