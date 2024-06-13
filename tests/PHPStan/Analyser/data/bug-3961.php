<?php // onlyif PHP_VERSION_ID < 80000

namespace Bug3961;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(string $v, string $d, $m): void
	{
		assertType('non-empty-list<string>', explode('.', $v));
		assertType('false', explode('', $v));
		assertType('list<string>', explode('.', $v, -2));
		assertType('non-empty-list<string>', explode('.', $v, 0));
		assertType('non-empty-list<string>', explode('.', $v, 1));
		assertType('non-empty-list<string>|false', explode($d, $v));
		assertType('(non-empty-list<string>|false)', explode($m, $v));
	}

}
