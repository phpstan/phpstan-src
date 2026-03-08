<?php

namespace IsCountable;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array|\Countable|string $union
	 */
	public function doFoo(
		$union
	)
	{
		if (is_countable($union)) {
			assertType('array|Countable', $union);
		} else {
			assertType('string', $union);
		}
	}

}
