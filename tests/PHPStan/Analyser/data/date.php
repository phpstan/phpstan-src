<?php

namespace DateFunction;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * see https://www.php.net/manual/en/datetime.format.php
	 */
	public function doFoo()
	{
		assertType('int<1, 31>', date('j'));
		assertType('int<1, 7>', date('N'));
		assertType('int<0, 6>', date('w'));
		assertType('int<0, 365>', date('z'));

		assertType('int<1, 53>', date('W'));

		assertType('int<1, 12>', date('n'));
		assertType('int<28, 31>', date('t'));

		assertType('int<0, 1>', date('L'));

		// we assume a max year of 9999
		assertType('int<1, 9999>', date('o'));
		assertType('int<1, 9999>', date('Y'));

		assertType('int<1, 12>', date('g'));
		assertType('int<0, 23>', date('G'));

		assertType('int<0, 1>', date('I'));
	}

}
