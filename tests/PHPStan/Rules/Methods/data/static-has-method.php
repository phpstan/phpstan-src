<?php declare(strict_types = 1);

namespace StaticHasMethodCall;

class rex_var {}

class HelloWorld
{
	public function sayHello(): void
	{
		if (!method_exists(rex_var::class, 'varsIterator')) {
			return;
		}

		$it = rex_var::varsIterator();
	}
}

