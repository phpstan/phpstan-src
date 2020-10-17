<?php

namespace Bug3961;

use function PHPStan\Analyser\assertType;

class Foo
{

	public function doFoo(string $v, string $d, $m): void
	{
		assertType('array<int, string>&nonEmpty', explode('.', $v));
		assertType('false', explode('', $v));
		assertType('array<int, string>', explode('.', $v, -2));
		assertType('array<int, string>&nonEmpty', explode('.', $v, 0));
		assertType('array<int, string>&nonEmpty', explode('.', $v, 1));
		assertType('array<int, string>|false', explode($d, $v));
		assertType('(array<int, string>|false)', explode($m, $v));
	}

}
