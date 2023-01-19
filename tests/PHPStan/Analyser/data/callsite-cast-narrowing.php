<?php

namespace CallsiteCastNarrowing;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param numeric-string $numericString
	 * @param non-empty-string $nonEmptyString
	 */
	public function sayHello($mixed, int $int, string $string, $numericString, $nonEmptyString, bool $bool): void
	{
		if (ctype_digit((string) $mixed)) {
			assertType('int<0, max>|numeric-string|true', $mixed);
		} else {
			assertType('mixed', $mixed);
		}
		assertType('mixed', $mixed);

		if (ctype_digit((int) $mixed)) {
			assertType('mixed', $mixed); // could be *NEVER*
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
			assertType('int', $int); // could be *NEVER*
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
			assertType('string', $string); // could be *NEVER*
		} else {
			assertType('string', $string);
		}
		assertType('string', $string);

		if (ctype_digit((string) $numericString)) {
			assertType('numeric-string', $numericString);
		} else {
			assertType('numeric-string', $numericString); // could be *NEVER*
		}
		assertType('numeric-string', $numericString);

		if (ctype_digit((string) $bool)) {
			assertType('true', $bool);
		} else {
			assertType('bool', $bool);
		}
		assertType('bool', $bool);
	}

}
