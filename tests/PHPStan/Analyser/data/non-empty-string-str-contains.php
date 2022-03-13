<?php

namespace NonEmptyStringStrContains;

use function PHPStan\Testing\assertType;

class Foo {
	/**
	 * @param non-empty-string $nonES
	 * @param numeric-string $numS
	 * @param literal-string $literalS
	 * @param non-empty-string&numeric-string $nonEAndNumericS
	 */
	public function sayHello(string $s, string $s2, $nonES, $numS, $literalS, $nonEAndNumericS, int $i): void
	{
		if (str_contains($s, ':')) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (str_contains($s, $s2)) {
			assertType('string', $s);
		}

		if (str_contains($s, $nonES)) {
			assertType('non-empty-string', $s);
		}

		if (str_contains($s, $numS)) {
			assertType('non-empty-string', $s);
		}

		if (str_contains($s, $literalS)) {
			assertType('string', $s);
		}

		if (str_contains($s, $nonEAndNumericS)) {
			assertType('non-empty-string', $s);
		}
		if (str_contains($numS, $nonEAndNumericS)) {
			assertType('non-empty-string&numeric-string', $numS);
		}

		if (str_contains($i, $s2)) {
			assertType('int', $i);
		}
	}

	public function variants(string $s) {
		if (str_starts_with($s, ':')) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (str_ends_with($s, ':')) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);
	}
}
