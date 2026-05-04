<?php

namespace Bug12517;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(\stdClass $foo): void
	{
		if ($foo->a !== null || $foo->b !== null) {
			if ($foo->a === null) {
				assertType('null', $foo->a);
				assertType('mixed~null', $foo->b);
			}
		}

		$a = $foo->a;
		$b = $foo->b;
		if ($a !== null || $b !== null) {
			if ($a === null) {
				assertType('null', $a);
				assertType('mixed~null', $b);
			}
		}
	}
}

class Test
{
	/** @var mixed */
	public static $a = null;
	/** @var mixed */
	public static $b = null;

	public function sayHello(): void
	{
		if (Test::$a !== null || Test::$b !== null) {
			if (Test::$a === null) {
				assertType('null', Test::$a);
				assertType('mixed~null', Test::$b);
			}
		}

		$a = Test::$a;
		$b = Test::$b;
		if ($a !== null || $b !== null) {
			if ($a === null) {
				assertType('null', $a);
				assertType('mixed~null', $b);
			}
		}
	}
}

class WithArray
{
	public function sayHello(array $array): void
	{
		if ($array['a'] !== null || $array['b'] !== null) {
			if ($array['a'] === null) {
				assertType('null', $array['a']);
				assertType('mixed~null', $array['b']);
			}
		}

		$a = $array['a'];
		$b = $array['b'];
		if ($a !== null || $b !== null) {
			if ($a === null) {
				assertType('null', $a);
				assertType('mixed~null', $b);
			}
		}
	}
}
