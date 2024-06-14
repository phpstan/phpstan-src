<?php

namespace ForeachPartiallyNonIterable;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array<string, int>|false $a
	 */
	public function doFoo($a): void
	{
		foreach ($a as $k => $v) {
			assertType('string', $k);
			assertType('int', $v);
		}
	}

}

class Bar
{

	public function sayHello(\stdClass $s): void
	{
		$a = null;
		foreach ($s as $k => $v) {
			$a .= 'test';
		}
		assertType('(literal-string&non-falsy-string)|null', $a);
	}

}
