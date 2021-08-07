<?php

namespace Substr;

use function PHPStan\Testing\assertType;

class Substr
{
	public function doFoo(string $s, int $i)
	{
		assertType('string', substr(null, true));
		assertType('string', substr(true, true));
		assertType('string', substr([], true));
		assertType('string', substr('a', 2));
		assertType('string', substr($i, 2));
		assertType('string', substr($s, $i));
		assertType('string', substr(3.1, $i));
	}
}
