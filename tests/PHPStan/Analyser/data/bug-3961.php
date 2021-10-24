<?php

namespace Bug3961;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(string $v, string $d, $m): void
	{
		assertType('non-empty-array<int, string>', explode('.', $v));
		assertType('false', explode('', $v));
		assertType('array<int, string>', explode('.', $v, -2));
		assertType('non-empty-array<int, string>', explode('.', $v, 0));
		assertType('non-empty-array<int, string>', explode('.', $v, 1));
		assertType('non-empty-array<int, string>|false', explode($d, $v));
		assertType('(non-empty-array<int, string>|false)', explode($m, $v));
	}

}
