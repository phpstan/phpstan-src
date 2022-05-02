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
		assertType('class-string<stdClass>', $std::class);
		assertType('*ERROR*', $string::class);
		assertType('class-string<stdClass>|null', $stdOrNull::class);
		assertType('*ERROR*', $stringOrNull::class);
	}

}
