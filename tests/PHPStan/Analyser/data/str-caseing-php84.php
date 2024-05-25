<?php

namespace StrCasingReturnTypePhp84;

use function PHPStan\Testing\assertType;

class Foo {
	/**
	 * @param numeric-string $numericS
	 * @param non-empty-string $nonE
	 * @param literal-string $literal
	 * @param 'foo'|'Foo' $edgeUnion
	 */
	public function bar($numericS, $nonE, $literal) {
		assertType("'aBC'", mb_lcfirst('ABC'));
		assertType("'Abc'", mb_ucfirst('abc'));
		assertType("numeric-string", mb_lcfirst($numericS));
		assertType("numeric-string", mb_ucfirst($numericS));
		assertType("non-empty-string", mb_lcfirst($nonE));
		assertType("non-empty-string", mb_ucfirst($nonE));
		assertType("string", mb_lcfirst($literal));
		assertType("string", mb_ucfirst($literal));
	}
}
