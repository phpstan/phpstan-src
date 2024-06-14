<?php

namespace ClassConstantOnExprAssertType;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(
		\stdClass $std,
		string $string,
		?\stdClass $stdOrNull,
		?string $stringOrNull
	): void
	{
		assertType('class-string<stdClass>&literal-string', $std::class);
		assertType('*ERROR*', $string::class);
		assertType('(class-string<stdClass>&literal-string)|null', $stdOrNull::class);
		assertType('*ERROR*', $stringOrNull::class);
		assertType("'Foo'", 'Foo'::class);
	}

}
