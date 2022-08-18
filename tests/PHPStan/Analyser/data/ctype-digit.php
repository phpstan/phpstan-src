<?php declare(strict_types=1);

namespace CtypeDigit;

use function PHPStan\Testing\assertType;

class Foo
{
	public function foo(mixed $foo): void
	{
		if (is_string($foo) && ctype_digit($foo)) {
			assertType('numeric-string', $foo);
		}
		if (is_int($foo) && ctype_digit($foo)) {
			assertType('int<48, 57>|int<256, max>', $foo);
		}
		if (ctype_digit($foo)) {
			assertType('int<48, 57>|int<256, max>|numeric-string', $foo);
		}
	}
}
