<?php

namespace Substr;

use function PHPStan\Testing\assertType;

class Substr
{
	public function doFoo(string $s, int $i)
	{
		assertType('false', substr(null, true));
		assertType('false', substr('a', 2));
		assertType('string|false', substr($i, 2));
		assertType('string|false', substr($s, $i));
	}
}
