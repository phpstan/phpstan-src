<?php

namespace SwitchTypeElimination;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param string|int $stringOrInt
	 */
	public function doFoo($stringOrInt)
	{
		switch (true) {
			case is_int($stringOrInt):
				break;
			case doFoo():
				assertType('string', $stringOrInt);
		}
	}

}
