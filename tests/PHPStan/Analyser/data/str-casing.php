<?php

namespace StrCasingReturnType;

use function PHPStan\Testing\assertType;

class Foo {
	/**
	 * @param numeric-string $numericS
	 * @param non-empty-string $nonE
	 * @return string
	 */
	public function bar($numericS, $nonE): string {
		assertType("'abc'", strtolower('ABC'));
		assertType("'ABC'", strtoupper('abc'));
		assertType("'abc'", mb_strtolower('ABC'));
		assertType("'ABC'", mb_strtoupper('abc'));
		assertType("'aBC'", lcfirst('ABC'));
		assertType("'Abc'", ucfirst('abc'));
		assertType("'Hello World'", ucwords('hello world'));

		assertType("numeric-string", strtolower($numericS));
		assertType("numeric-string", strtoupper($numericS));
		assertType("numeric-string", mb_strtolower($numericS));
		assertType("numeric-string", mb_strtoupper($numericS));
		assertType("numeric-string", lcfirst($numericS));
		assertType("numeric-string", ucfirst($numericS));
		assertType("numeric-string", ucwords($numericS));

		assertType("non-empty-string", strtolower($nonE));
		assertType("non-empty-string", strtoupper($nonE));
		assertType("non-empty-string", mb_strtolower($nonE));
		assertType("non-empty-string", mb_strtoupper($nonE));
		assertType("non-empty-string", lcfirst($nonE));
		assertType("non-empty-string", ucfirst($nonE));
		assertType("non-empty-string", ucwords($nonE));
	}
}
