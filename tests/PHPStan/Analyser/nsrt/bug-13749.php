<?php declare(strict_types = 1);

namespace Bug13749;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param non-empty-string $nonES
	 */
	public function sayNonEmpty(string $s, string $nonES): void
	{
		if (strlen($nonES) === strlen($s)) {
			assertType('non-empty-string', $s);
		}
		if (strlen($nonES) >= strlen($s)) {
			assertType('string', $s); // could be non-empty-string
		}

		if (strlen($s) === strlen($nonES)) {
			assertType('non-empty-string', $s);
		}
		if (strlen($s) >= strlen($nonES)) {
			assertType('non-empty-string', $s);
		}
	}

	/**
	 * @param non-falsy-string $nonFalsy
	 */
	public function sayNonFalsy(string $s, string $nonFalsy): void
	{
		if (strlen($nonFalsy) === strlen($s)) {
			assertType('non-empty-string', $s);
		}
		if (strlen($nonFalsy) >= strlen($s)) {
			assertType('string', $s); // could be non-empty-string
		}

		if (strlen($s) === strlen($nonFalsy)) {
			assertType('non-empty-string', $s);
		}
		if (strlen($s) >= strlen($nonFalsy)) {
			assertType('non-empty-string', $s);
		}
	}

}
