<?php

namespace Bug2899;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(string $s, $mixed)
	{
		assertType('int<1, 9999>', date('Y'));
		assertType('string', date('Y.m.d'));
		assertType('string', date($s));
		assertType('string', date($mixed));
	}

}
