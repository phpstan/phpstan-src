<?php

namespace DynamicSprintf;

use function PHPStan\Testing\assertType;
use function sprintf;

class Foo
{

	/**
	 * @param 'a'|'aa' $a
	 * @param 'b'|'bb' $b
	 * @param 'c'|'cc' $c
	 */
	public function doFoo(string $a, string $b, string $c): void
	{
		assertType("'a b c'|'a b cc'|'a bb c'|'a bb cc'|'aa b c'|'aa b cc'|'aa bb c'|'aa bb cc'", sprintf('%s %s %s', $a, $b, $c));
	}

}
