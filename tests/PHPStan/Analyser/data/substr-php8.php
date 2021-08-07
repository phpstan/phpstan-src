<?php

namespace Substr;

use function PHPStan\Testing\assertType;

class Substr
{
	public function doFoo(string $s, int $i)
	{
		assertType('string', substr(null, true));
		assertType('string', substr(true, true));
		assertType('*NEVER*', substr([], true));
		assertType('*NEVER*', substr(new \stdClass(), true));
		assertType('string', substr('a', 2));
		assertType('string', substr($i, 2));
		assertType('string', substr($s, $i));
		assertType('string', substr(3.1, $i));
		assertType('"lo"', substr("hallo", 3));
		assertType('"l"', substr("hallo", 3, 1));
		assertType('string', substr("hallo", 10));
		assertType('string', substr("hallo", 10, 1));
	}
}
