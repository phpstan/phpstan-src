<?php

namespace CallsiteCastNarrowing;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello($mixed, int $int, string $string): void
	{
		if (ctype_digit((string) $mixed)) {
			assertType('int<48, 57>|int<256, max>|numeric-string', $mixed);
		} else {
			assertType('mixed', $mixed);
		}
		assertType('mixed', $mixed);

		if (ctype_digit((int) $mixed)) {
			assertType('int<48, 57>|int<256, max>|numeric-string', $mixed);
		} else {
			assertType('mixed', $mixed);
		}
		assertType('mixed', $mixed);

		if (ctype_digit((string) $int)) {
			assertType('int', $int);
		} else {
			assertType('int', $int);
		}
		assertType('int', $int);

		if (ctype_digit((int) $int)) {
			assertType('int<48, 57>|int<256, max>', $int);
		} else {
			assertType('int', $int);
		}
		assertType('int', $int);

		if (ctype_digit((string) $string)) {
			assertType('numeric-string', $string);
		} else {
			assertType('string', $string);
		}
		assertType('string', $string);

		if (ctype_digit((int) $string)) {
			assertType('numeric-string', $string);
		} else {
			assertType('string', $string);
		}
		assertType('string', $string);
	}

}
