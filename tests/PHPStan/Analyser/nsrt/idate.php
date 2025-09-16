<?php

namespace PHPStan\Analyser\nsrt;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param 'h'|'H' $hour
	 * @param 'm'|string $format
	 */
	public function doFoo(string $string, string $hour, string $format): void
	{
		assertType('int<1, 31>', idate('j'));
		assertType('int<1, 7>', idate('N'));
		assertType('int', idate('Y'));
		assertType('false', idate('wrong'));
		assertType('false', idate(''));
		assertType('int|false', idate($string));
		assertType('int<0, 23>', idate($hour));
		assertType('int|false', idate($format));
	}

}
