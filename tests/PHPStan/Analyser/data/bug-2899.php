<?php

namespace Bug2899;

use function PHPStan\Analyser\assertType;

class Foo
{

	public function doFoo(string $s, $mixed)
	{
		assertType('string&numeric', date('Y'));
		assertType('string', date('Y.m.d'));
		assertType('string', date($s));
		assertType('string', date($mixed));
	}

}
