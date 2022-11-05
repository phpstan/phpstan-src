<?php declare(strict_types = 1);

namespace StaticHasMethodCall;

class rex_var {
	public static function aMethod() {}
}

class HelloWorld
{
	public function sayHello(): void
	{
		if (!method_exists(rex_var::class, 'doesNotExist')) {
			return;
		}

		// should not error
		$it = rex_var::doesNotExist();
	}

	public function sayHello2(): void
	{
		if (!method_exists(rex_var::class, 'doesNotExist')) {
			return;
		}

		// should not error
		$it = rex_var::aMethod();
	}

	public function sayHello3(): void
	{
		if (!method_exists(rex_var::class, 'anotherNotExistingMethod')) {
			return;
		}

		// should error
		$it = rex_var::doesNotExist();
	}

	public function sayHello4(): void
	{
		if (!method_exists(notExistentClass::class, 'doesNotExist')) {
			return;
		}

		// should error
		$it = rex_var::doesNotExist();
	}
}

