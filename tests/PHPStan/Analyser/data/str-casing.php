<?php

namespace StrCasingReturnType;

use function PHPStan\Testing\assertType;

class Foo {
	/**
	 * @param numeric-string $numericS
	 * @param non-empty-string $nonE
	 * @param literal-string $literal
	 */
	public function bar($numericS, $nonE, $literal) {
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

		assertType("literal-string", strtolower($literal));
		assertType("literal-string", strtoupper($literal));
		assertType("literal-string", mb_strtolower($literal));
		assertType("literal-string", mb_strtoupper($literal));
		assertType("literal-string", lcfirst($literal));
		assertType("literal-string", ucfirst($literal));
		assertType("literal-string", ucwords($literal));
	}

	public function foo() {
		// calls with a 2nd arg could be more precise, but there was no use-case yet to support it
		assertType("literal-string&non-empty-string", mb_strtolower('ABC', 'UTF-8'));
		assertType("literal-string&non-empty-string", mb_strtoupper('abc', 'UTF-8'));
		assertType("literal-string&non-empty-string", ucwords('hello|world!', "|"));

		// invalid char conversions still lead to non-empty-string
		assertType("literal-string&non-empty-string", mb_strtolower("\xfe\xff\x65\xe5\x67\x2c\x8a\x9e", 'CP1252'));

	}
}
