<?php

namespace Bug4838;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{

	public function doIt(): void
	{
		try {
			$handle = tmpfile();

			if (rand(1,10) > 5) {
				throw new \LogicException();
			}
		} finally {
			assertVariableCertainty(TrinaryLogic::createYes(), $handle);
		}
	}

}
