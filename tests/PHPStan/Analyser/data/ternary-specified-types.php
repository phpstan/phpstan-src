<?php

namespace TernarySpecifiedTypes;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(bool $a, bool $b)
	{
		if ($a ? $b : false) {
			// $a && $b
			assertType('true', $a);
			assertType('true', $b);
		} else {
			// !$a || !$b
			assertType('bool', $a);
			assertType('bool', $b);
			if (!$a) {
				assertType('false', $a);
				assertType('bool', $b);
			} else {
				assertType('true', $a);
				assertType('false', $b);
			}
		}
	}

	public function doBar(bool $a)
	{
		if ($a ?: false) {
			assertType('true', $a);
		} else {
			assertType('false', $a);
		}
	}

}
