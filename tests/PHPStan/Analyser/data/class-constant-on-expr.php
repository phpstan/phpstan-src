<?php

namespace ClassConstantOnExprAssertType;

use function PHPStan\Analyser\assertType;

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
		assertType('class-string<stdClass>', $stdOrNull::class);
		assertType('*ERROR*', $stringOrNull::class);
	}

}
