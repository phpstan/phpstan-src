<?php

namespace ClosurePassedByReference;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(\Closure $closure): int
	{
		return 5;
	}

	public function doBar()
	{
		$five = $this->doFoo(function () use (&$five) {
			assertType('int|null', $five);
		});
	}

}
