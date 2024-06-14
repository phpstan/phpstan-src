<?php declare(strict_types = 1);

namespace StaticHasMethod;

use function PHPStan\Testing\assertType;

class rex_var {}

class HelloWorld
{
	public function sayHello(): void
	{
		if (!method_exists(rex_var::class, 'varsIterator')) {
			return;
		}
		assertType("'StaticHasMethod\\\\rex_var'&hasMethod(varsIterator)", rex_var::class);

		$it = rex_var::varsIterator();
	}
}

