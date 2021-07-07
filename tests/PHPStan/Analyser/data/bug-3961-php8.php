<?php

namespace Bug3961Php8;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(string $v, string $d, $m): void
	{
		assertType('array<int, string>&nonEmpty', explode('.', $v));
		assertType('*NEVER*', explode('', $v));
		assertType('array<int, string>', explode('.', $v, -2));
		assertType('array<int, string>&nonEmpty', explode('.', $v, 0));
		assertType('array<int, string>&nonEmpty', explode('.', $v, 1));
		assertType('array<int, string>&nonEmpty', explode($d, $v));
		assertType('array<int, string>&nonEmpty', explode($m, $v));
	}

}
