<?php

namespace ThrowsTagFromNativeFunction;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{

	public function doFoo(): void
	{
		try {
			$int = random_int(1, 2);
		} finally {
			assertVariableCertainty(TrinaryLogic::createYes(), $int);
		}
	}

}
