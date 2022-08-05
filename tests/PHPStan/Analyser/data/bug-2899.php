<?php

namespace Bug2899;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(string $s, $mixed)
	{
		assertType('numeric-string', date('Y'));
		assertType('non-falsy-string', date('Y.m.d'));
		assertType('string', date($s));
		assertType('string', date($mixed));
	}

}
