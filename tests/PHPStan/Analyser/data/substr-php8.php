<?php

namespace Substr;

use function PHPStan\Testing\assertType;

class Substr
{
	public function doFoo(string $s, int $i)
	{
		// fatal error cases
		assertType('*NEVER*', substr([], true));
		assertType('*NEVER*', substr(new \stdClass(), true));
		// error cases
		assertType("''", substr(null, true));
		assertType("''", substr(true, true));
		assertType("''", substr('a', 2));
		assertType("''", substr("hallo", 10));
		assertType("''", substr("hallo", 10, 1));
		// happy path
		assertType("'lo'", substr("hallo", 3));
		assertType("'l'", substr("hallo", 3, 1));
		assertType('string', substr($i, 2));
		assertType('string', substr($s, $i));
		assertType('string', substr(3.1, $i));
	}
}
