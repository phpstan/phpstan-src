<?php

namespace BlockStatement;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{

	public function doFoo(): void
	{
		$a = 1;
		{
			$b = 2;
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertVariableCertainty(TrinaryLogic::createYes(), $b);
	}

}
