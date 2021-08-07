<?php

namespace Substr;

use function PHPStan\Testing\assertType;

class Substr
{
	public function doFoo(string $s, int $i)
	{
		// fatal error cases
		assertType('null', substr([], true));
		assertType('null', substr(new \stdClass(), true));
		// error cases
		assertType('false', substr(null, true));
		assertType('false', substr(true, true));
		assertType('false', substr('a', 2));
		assertType('false', substr("hallo", 10));
		assertType('false', substr("hallo", 10, 1));
		// happy path
		assertType("'lo'", substr("hallo", 3));
		assertType("'l'", substr("hallo", 3, 1));
		assertType('string|false', substr($i, 2));
		assertType('string|false', substr($s, $i));
		assertType('string|false', substr(3.1, $i));
	}
}
